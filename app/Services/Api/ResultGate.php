<?php

namespace App\Services\Api;

/**
 * Result hold check — TopClass edition.
 *
 * TopClass has no unpaid-fees result hold, so every term is allowed here.
 * Whether parents and students may see results at all is set by the admin
 * under Admin → Result display (App\Services\Results\ResultDisplay::viewerMaySee);
 * MOBILE_PARENT_RESULTS only provides that page's starting value.
 * Same shape as ResultAccessService::check() on other portals.
 */
class ResultGate
{
    public function check(int $studentId, int $termId, int $sessionId, bool $log = true): array
    {
        return ['allowed' => true];
    }
}
