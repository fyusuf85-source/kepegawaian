<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    /**
     * Nilai yang tidak boleh tersimpan mentah di audit log.
     *
     * @var array<int, string>
     */
    protected array $sensitiveKeys = [
        'password',
        'remember_token',
        'nik',
        'nik_hash',
        'account_number',
        'npwp',
        'bpjs_kesehatan_no',
        'bpjs_tk_no',
    ];

    public function record(string $event, Model $model, ?array $old = null, ?array $new = null): void
    {
        $this->write(
            $event,
            $model->getMorphClass(),
            $model->getKey(),
            $old,
            $new
        );
    }

    public function recordRaw(
        string $event,
        string $auditableType,
        ?int $auditableId = null,
        ?array $old = null,
        ?array $new = null
    ): void {
        $this->write($event, $auditableType, $auditableId, $old, $new);
    }

    protected function write(string $event, string $type, ?int $id, ?array $old, ?array $new): void
    {
        $request = request();

        DB::table('audit_logs')->insert([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'old_values' => $old === null ? null : json_encode($this->sanitize($old), JSON_UNESCAPED_UNICODE),
            'new_values' => $new === null ? null : json_encode($this->sanitize($new), JSON_UNESCAPED_UNICODE),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sanitize(array $values): array
    {
        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array($key, $this->sensitiveKeys, true)) {
                $clean[$key] = $value === null ? null : '[disembunyikan]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
