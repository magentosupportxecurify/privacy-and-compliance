<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Model;

/**
 * Free-tier stub — no data deletion logic.
 *
 * All actual deletion logic lives in
 * MiniOrange\PDProtectPremium\Model\CustomerDataEraser,
 * which is injected via DI preference when the Premium extension is installed.
 */
class CustomerDataEraser
{
    public function execute(int $customerId): void
    {
        // Premium feature — no-op in free extension.
    }
}
