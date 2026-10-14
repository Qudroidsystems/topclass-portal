<?php

namespace App\Services\Api;

/**
 * Whether results may be shown in the mobile app — TopClass edition.
 *
 * TopClass has no unpaid-fees result hold, so this only applies the school's
 * switch: MOBILE_PARENT_RESULTS=false hides results (and the report card,
 * progress chart and guidance built from them) from parents and students.
 * Same shape as ResultAccessService::check() on other portals.
 */
class ResultGate
{
    public function check(int $studentId, int $termId, int $sessionId, bool $log = true): array
    {
        if (!config('services.mobile.parent_results', true)) {
            return ['allowed' => false, 'reason' => 'not_released',
                    'message' => 'The school has not made results available in the app yet.', 'owed' => 0];
        }
        return ['allowed' => true];
    }
}
