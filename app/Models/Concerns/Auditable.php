<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogger::class)->record('create', $model, null, $model->attributesToArray());
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if ($changes === []) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $model->getOriginal($key);
            }

            app(AuditLogger::class)->record('update', $model, $old, $changes);
        });

        static::deleted(function (Model $model): void {
            app(AuditLogger::class)->record('delete', $model, $model->attributesToArray(), null);
        });
    }
}
