<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\ScopeInterface;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override of PDProtect\Model\ConsentLogger.
 *
 * Adds a module-active gate so that consent events are not recorded when the
 * trial has expired and no license is active. This covers the free module's
 * CustomerLoginObserver, which calls ConsentLogger directly and cannot be
 * intercepted without modifying the free module.
 *
 * Note: PDProtectPremium\Model\ConsentLogCleaner is a different class (log cleanup).
 */
class ConsentLogger extends \MiniOrange\PDProtect\Model\ConsentLogger
{
    private const CONFIG_LOG_CONSENT = 'pdprotect/general/log_guest_consent';

    public function __construct(
        ResourceConnection $resource,
        RemoteAddress $remoteAddress,
        private readonly PremiumHelper $premiumHelper,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($resource, $remoteAddress);
    }

    public function log(
        string $status,
        ?int $customerId = null,
        string $countryCode = '',
        ?string $email = null
    ): void {
        if (!$this->premiumHelper->isModuleActive()) {
            return;
        }
        if (!(bool) $this->scopeConfig->getValue(self::CONFIG_LOG_CONSENT, ScopeInterface::SCOPE_STORE)) {
            return;
        }
        parent::log($status, $customerId, $countryCode, $email);
    }
}
