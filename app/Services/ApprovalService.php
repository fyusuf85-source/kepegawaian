<?php

namespace App\Services;

use App\Contracts\Approvable;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalInstance;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function submit(Approvable $subject, User $requester): ApprovalInstance
    {
        if (! $subject instanceof Model) {
            throw ValidationException::withMessages(['approval' => 'Objek persetujuan tidak valid.']);
        }

        return DB::transaction(function () use ($subject, $requester): ApprovalInstance {
            $existing = ApprovalInstance::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->where('status', 'menunggu')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'approval' => 'Dokumen ini sudah memiliki pengajuan persetujuan yang masih berjalan.',
                ]);
            }

            $flow = ApprovalFlow::query()
                ->where('code', $subject->approvalFlowCode())
                ->where('is_active', true)
                ->with('steps')
                ->first();

            if (! $flow || $flow->steps->isEmpty()) {
                throw ValidationException::withMessages([
                    'approval' => 'Alur persetujuan belum dikonfigurasi untuk jenis dokumen ini.',
                ]);
            }

            foreach ($flow->steps->where('is_optional', false) as $step) {
                if ($this->candidates($step, $subject)->isEmpty()) {
                    throw ValidationException::withMessages([
                        'approval' => 'Tidak ada pejabat aktif yang memenuhi syarat pada langkah '.$step->sequence.'.',
                    ]);
                }
            }

            $instance = ApprovalInstance::create([
                'approval_flow_id' => $flow->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'status' => 'menunggu',
                'current_step' => $flow->steps->first()->sequence,
                'requested_by' => $requester->id,
                'requested_at' => now(),
            ]);

            $subject->markApprovalPending();
            $this->notifyStep($instance->fresh('flow.steps'), $subject);

            return $instance;
        });
    }

    public function approve(ApprovalInstance $instance, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($instance, $actor, $note): void {
            $instance = ApprovalInstance::query()->lockForUpdate()->findOrFail($instance->id);
            $this->assertActionable($instance, $actor);

            $step = $instance->currentStep();
            if (! $step) {
                throw ValidationException::withMessages(['approval' => 'Langkah persetujuan tidak ditemukan.']);
            }

            $this->assertActorAllowed($instance, $step, $actor);

            $instance->actions()->create([
                'sequence' => $step->sequence,
                'actor_id' => $actor->id,
                'action' => 'setuju',
                'note' => $note,
                'acted_at' => now(),
            ]);

            $next = $this->nextStep($instance, $step->sequence);

            if ($next) {
                $instance->update(['current_step' => $next->sequence]);
                $subject = $instance->subject;
                if ($subject instanceof Approvable) {
                    $this->notifyStep($instance->fresh('flow.steps'), $subject);
                }

                return;
            }

            $instance->update([
                'status' => 'disetujui',
                'completed_at' => now(),
            ]);

            $subject = $instance->subject;
            if ($subject instanceof Approvable) {
                $subject->markApprovalApproved($actor);
                $this->notifications->send(
                    $instance->requester,
                    'approval',
                    'Pengajuan disetujui',
                    $subject->approvalLabel().' telah disetujui.',
                    ['approval_instance_id' => $instance->id]
                );
            }
        });
    }

    public function reject(ApprovalInstance $instance, User $actor, string $note): void
    {
        if (trim($note) === '') {
            throw ValidationException::withMessages(['note' => 'Alasan penolakan wajib diisi.']);
        }

        DB::transaction(function () use ($instance, $actor, $note): void {
            $instance = ApprovalInstance::query()->lockForUpdate()->findOrFail($instance->id);
            $this->assertActionable($instance, $actor);

            $step = $instance->currentStep();
            if (! $step) {
                throw ValidationException::withMessages(['approval' => 'Langkah persetujuan tidak ditemukan.']);
            }

            $this->assertActorAllowed($instance, $step, $actor);

            $instance->actions()->create([
                'sequence' => $step->sequence,
                'actor_id' => $actor->id,
                'action' => 'tolak',
                'note' => $note,
                'acted_at' => now(),
            ]);

            $instance->update([
                'status' => 'ditolak',
                'completed_at' => now(),
            ]);

            $subject = $instance->subject;
            if ($subject instanceof Approvable) {
                $subject->markApprovalRejected($actor, $note);
                $this->notifications->send(
                    $instance->requester,
                    'approval',
                    'Pengajuan ditolak',
                    $subject->approvalLabel().' ditolak: '.$note,
                    ['approval_instance_id' => $instance->id]
                );
            }
        });
    }

    public function cancel(ApprovalInstance $instance, User $actor): void
    {
        DB::transaction(function () use ($instance, $actor): void {
            $instance = ApprovalInstance::query()->lockForUpdate()->findOrFail($instance->id);

            if (! $instance->isPending()) {
                throw ValidationException::withMessages(['approval' => 'Pengajuan tidak berada pada status menunggu.']);
            }

            if ($instance->requested_by !== $actor->id && ! $actor->hasRole('administrator')) {
                throw ValidationException::withMessages(['approval' => 'Hanya pengaju yang dapat membatalkan pengajuan.']);
            }

            $instance->actions()->create([
                'sequence' => $instance->current_step,
                'actor_id' => $actor->id,
                'action' => 'kembalikan',
                'note' => 'Dibatalkan oleh pengaju.',
                'acted_at' => now(),
            ]);

            $instance->update(['status' => 'dibatalkan', 'completed_at' => now()]);
        });
    }

    /**
     * Daftar pengajuan yang menunggu keputusan pengguna.
     *
     * @return Collection<int, ApprovalInstance>
     */
    public function pendingForUser(User $user): Collection
    {
        return ApprovalInstance::query()
            ->pending()
            ->with(['flow.steps', 'requester', 'subject'])
            ->orderBy('requested_at')
            ->get()
            ->filter(function (ApprovalInstance $instance) use ($user): bool {
                $step = $instance->currentStep();
                if (! $step) {
                    return false;
                }

                $subject = $instance->subject;

                return $subject instanceof Approvable
                    && $this->scopeAllows($user, $step, $subject)
                    && $this->hasStepRole($user, $step);
            })
            ->values();
    }

    protected function assertActionable(ApprovalInstance $instance, User $actor): void
    {
        if (! $instance->isPending()) {
            throw ValidationException::withMessages(['approval' => 'Pengajuan sudah selesai diproses.']);
        }

        if ($instance->requested_by === $actor->id) {
            throw ValidationException::withMessages([
                'approval' => 'Pengaju tidak boleh menyetujui atau menolak pengajuannya sendiri.',
            ]);
        }
    }

    protected function assertActorAllowed(ApprovalInstance $instance, ApprovalFlowStep $step, User $actor): void
    {
        $subject = $instance->subject;

        if (! $subject instanceof Approvable) {
            throw ValidationException::withMessages(['approval' => 'Objek pengajuan tidak valid.']);
        }

        if (! $this->hasStepRole($actor, $step) || ! $this->scopeAllows($actor, $step, $subject)) {
            throw ValidationException::withMessages([
                'approval' => 'Anda bukan pejabat yang berwenang pada langkah persetujuan ini.',
            ]);
        }
    }

    protected function hasStepRole(User $user, ApprovalFlowStep $step): bool
    {
        return $user->roles()->where('roles.id', $step->role_id)->exists();
    }

    protected function scopeAllows(User $user, ApprovalFlowStep $step, Approvable $subject): bool
    {
        return match ($step->scope) {
            'all' => true,
            'unit' => $subject->approvalOrgUnitId() !== null
                && in_array($subject->approvalOrgUnitId(), $user->scopedOrgUnitIds(), true),
            'self' => method_exists($subject, 'employee')
                && $subject->employee?->direct_supervisor_id === $user->employee_id,
            default => false,
        };
    }

    /**
     * @return Collection<int, User>
     */
    protected function candidates(ApprovalFlowStep $step, Approvable $subject): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('roles.id', $step->role_id))
            ->get()
            ->filter(fn (User $user) => $this->scopeAllows($user, $step, $subject))
            ->values();
    }

    protected function nextStep(ApprovalInstance $instance, int $current): ?ApprovalFlowStep
    {
        $subject = $instance->subject;
        $steps = $instance->flow->steps->where('sequence', '>', $current)->sortBy('sequence');

        foreach ($steps as $step) {
            if ($step->is_optional && $subject instanceof Approvable && $this->candidates($step, $subject)->isEmpty()) {
                continue;
            }

            return $step;
        }

        return null;
    }

    protected function notifyStep(ApprovalInstance $instance, Approvable $subject): void
    {
        $step = $instance->currentStep();

        if (! $step) {
            return;
        }

        $this->notifications->sendToUsers(
            $this->candidates($step, $subject),
            'approval',
            'Persetujuan diperlukan',
            $subject->approvalLabel().' menunggu keputusan Anda.',
            [
                'approval_instance_id' => $instance->id,
                'subject_type' => $instance->subject_type,
                'subject_id' => $instance->subject_id,
            ]
        );
    }
}
