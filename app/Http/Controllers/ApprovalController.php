<?php

namespace App\Http\Controllers;

use App\Models\ApprovalInstance;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvals)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('approvals.index', [
            'pending' => $this->approvals->pendingForUser($user),
            'submitted' => ApprovalInstance::query()
                ->where('requested_by', $user->id)
                ->with(['flow', 'subject', 'actions.actor'])
                ->latest()
                ->paginate(15, ['*'], 'pengajuan'),
        ]);
    }

    public function show(Request $request, ApprovalInstance $approval): View
    {
        $approval->load(['flow.steps.role', 'actions.actor', 'requester', 'subject']);

        $this->authorizeView($request, $approval);

        return view('approvals.show', [
            'approval' => $approval,
            'canAct' => $approval->isPending()
                && $this->approvals->pendingForUser($request->user())->contains('id', $approval->id),
        ]);
    }

    public function approve(Request $request, ApprovalInstance $approval): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $this->approvals->approve($approval, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Pengajuan disetujui.');
    }

    public function reject(Request $request, ApprovalInstance $approval): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        $this->approvals->reject($approval, $request->user(), $data['note']);

        return back()->with('success', 'Pengajuan ditolak.');
    }

    public function cancel(Request $request, ApprovalInstance $approval): RedirectResponse
    {
        $this->approvals->cancel($approval, $request->user());

        return back()->with('success', 'Pengajuan dibatalkan.');
    }

    protected function authorizeView(Request $request, ApprovalInstance $approval): void
    {
        $user = $request->user();

        $allowed = $approval->requested_by === $user->id
            || $user->hasRole('administrator')
            || $this->approvals->pendingForUser($user)->contains('id', $approval->id);

        abort_unless($allowed, 403, 'Anda tidak memiliki akses ke pengajuan ini.');
    }
}
