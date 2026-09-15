<?php

namespace App\Support;

use App\Models\AuditLog as AuditLogModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class AuditLog
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?Authenticatable $actor = null,
    ): AuditLogModel {
        $actor ??= auth()->user();

        return AuditLogModel::query()->create([
            'actor_type' => $actor instanceof Model ? $actor::class : null,
            'actor_id' => $actor?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 512) ?: null,
            'properties' => $properties === [] ? null : $properties,
            'created_at' => now(),
        ]);
    }
}
