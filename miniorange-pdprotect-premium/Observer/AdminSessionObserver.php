<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;
use MiniOrange\PDProtectPremium\Helper\TrackingService;

/**
 * Premium session observer: initialises trial on first PDProtect admin visit and tracks trial events.
 * Fires on controller_action_predispatch (adminhtml area).
 * Route-guarded to only run on PDProtect pages — mirrors TwoFA calling isFirstPageVisit() from controllers.
 */
class AdminSessionObserver implements ObserverInterface
{
    /** Maps admin controller names to human-readable tab labels (mirrors TwoFA's $page parameter) */
    private const TAB_NAMES = [
        'generalsettings'   => 'General Settings',
        'customerprivacy'   => 'Customer Privacy',
        'datadeletion'      => 'Data Deletion',
        'datadeletionconfig'=> 'Data Deletion Config',
        'consentlogs'       => 'Consent Logs',
        'emailnotifications'=> 'Email Notifications',
        'upgrade'           => 'Upgrade',
        'account'           => 'Account',
    ];

    public function __construct(
        private readonly PremiumHelper $helper,
        private readonly TrackingService $trackingService,
        private readonly RequestInterface $request
    ) {}

    public function execute(Observer $observer): void
    {
        // Only fire on PDProtect admin pages — mirrors TwoFA calling from Account controller
        if ($this->request->getRouteName() !== 'mopdp') {
            return;
        }

        // Set installation date on first ever admin visit with premium active
        if (!$this->helper->getInstallationDate()) {
            $this->helper->setInstallationDate(date('Y-m-d H:i:s'));
        }

        // Start trial on first admin visit if not yet started
        if (!$this->helper->getTrialStartDate()) {
            $this->helper->setTrialStartDate(date('Y-m-d H:i:s'));
        }

        // Mirrors TwoFA premium: send trial tracking if TRIAL_PLAN_CONSTANT not yet set.
        // Handles both "premium-only install" and "free → premium upgrade" scenarios.
        // When free was installed first, pass its timestamp so both installs share one tracking date.
        if (!$this->helper->isTrialPlanConstantSet()) {
            $freeTimestamp = $this->helper->getFreeInstallTimestamp();
            $this->trackingService->trackTrialInstall($this->getTabName(), $freeTimestamp);
            return;
        }

        // PDProtect-specific: track trial expiry once (TwoFA does not have this)
        if ($this->helper->isTrialExpired()
            && !$this->helper->isLicenseVerified()
            && !$this->helper->isTrialExpiredTracked()
        ) {
            $this->trackingService->trackTrialExpired();
        }
    }

    private function getTabName(): string
    {
        $controller = strtolower((string) $this->request->getControllerName());
        return self::TAB_NAMES[$controller] ?? ucwords(str_replace('_', ' ', $controller));
    }
}
