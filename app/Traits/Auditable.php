<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            self::logChange('CREATE', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = array_intersect_key($model->getOriginal(), $model->getChanges());
            $new = $model->getChanges();
            self::logChange('UPDATE', $model, $old, $new);
        });

        static::deleted(function ($model) {
            self::logChange('DELETE', $model, $model->getAttributes(), null);
        });
    }

    protected static function logChange($action, $model, $old = null, $new = null)
    {
        try {
            AuditLog::create([
                'table_name' => $model->getTable(),
                'record_id' => $model->getKey(),
                'action' => $action,
                'changed_by_staff_id' => Auth::id(),
                'old_data_jsonb' => $old,
                'new_data_jsonb' => $new,
            ]);
        } catch (\Exception $e) {
            // Fail silently if audit table doesn’t exist yet
        }
    }
}
