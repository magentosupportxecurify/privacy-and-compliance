<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * No-op override of the free module's AdminSessionObserver.
 *
 * When PDProtectPremium is installed, PDProtectPremium\Observer\AdminSessionObserver
 * owns all tracking (including consolidated free+premium payloads via getFreeInstallTimestamp()).
 * Suppressing the free observer prevents a duplicate API call when both modules are active.
 *
 * This class has no effect when only the free module is installed — the DI preference
 * that points to this class only exists in PDProtectPremium's di.xml.
 */
class FreeAdminSessionObserver implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        // Intentional no-op: PDProtectPremium's AdminSessionObserver handles all tracking.
    }
}
