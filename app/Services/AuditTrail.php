<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\User;

class AuditTrail
{
    public function record(
        string $event,
        ?User $user = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $metadata = []
    ): void {
        AuditEvent::query()->create([
            'user_id' => $user?->id,
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
