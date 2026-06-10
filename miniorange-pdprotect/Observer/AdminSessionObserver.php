<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MiniOrange\PDProtect\Helper\TrackingService;

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
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TrackingService $trackingService,
        private readonly RequestInterface $request
    ) {}

    public function execute(Observer $observer): void
    {
        // Only fire when the admin is on a PDProtect page — mirrors TwoFA calling from Account controller
        if ($this->request->getRouteName() !== 'mopdp') {
            return;
        }

        // Guard: non-empty timestamp means tracking was already sent (mirrors TwoFA TIMESTAMP constant)
        $timestamp = $this->scopeConfig->getValue('pdprotect/tracking/timestamp');
        if (!empty($timestamp)) {
            return;
        }

        $this->trackingService->trackInstallation($this->getTabName());
    }

    private function getTabName(): string
    {
        $controller = strtolower((string) $this->request->getControllerName());
        return self::TAB_NAMES[$controller] ?? ucwords(str_replace('_', ' ', $controller));
    }
}
