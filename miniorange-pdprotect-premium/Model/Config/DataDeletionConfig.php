<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;

/**
 * Full implementation of data-deletion configuration for the premium module.
 * Overrides the free-module stub via DI preference.
 */
class DataDeletionConfig extends \MiniOrange\PDProtect\Model\Config\DataDeletionConfig
{
    private const PATH_ABANDONED_ENABLED   = 'mopdp/data_deletion/abandoned_enabled';
    private const PATH_ABANDONED_VALUE     = 'mopdp/data_deletion/abandoned_value';
    private const PATH_ABANDONED_UNIT      = 'mopdp/data_deletion/abandoned_unit';

    private const PATH_RECENT_ENABLED      = 'mopdp/data_deletion/recent_docs_enabled';
    private const PATH_RECENT_VALUE        = 'mopdp/data_deletion/recent_docs_value';
    private const PATH_RECENT_UNIT         = 'mopdp/data_deletion/recent_docs_unit';

    private const PATH_ORDER_ENABLED       = 'mopdp/data_deletion/order_status_enabled';
    private const PATH_ORDER_STATUSES      = 'mopdp/data_deletion/order_statuses';
    private const PATH_ORDER_ACTION        = 'mopdp/data_deletion/order_action';

    public function __construct(
        private readonly PDProtectHelper $pdProtectHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($scopeConfig);
    }

    // ── Abandoned customer deletion ───────────────────────────

    public function isAbandonedDeletionEnabled(): bool
    {
        return (bool) $this->pdProtectHelper->getStoreConfig(self::PATH_ABANDONED_ENABLED);
    }

    public function getAbandonedValue(): int
    {
        return (int) ($this->pdProtectHelper->getStoreConfig(self::PATH_ABANDONED_VALUE) ?: 2);
    }

    public function getAbandonedUnit(): string
    {
        return (string) ($this->pdProtectHelper->getStoreConfig(self::PATH_ABANDONED_UNIT) ?: 'years');
    }

    public function getAbandonedThresholdDays(): int
    {
        return $this->unitsToDays($this->getAbandonedValue(), $this->getAbandonedUnit());
    }

    // ── Recent documents deletion ─────────────────────────────

    public function isRecentDocsDeletionEnabled(): bool
    {
        return (bool) $this->pdProtectHelper->getStoreConfig(self::PATH_RECENT_ENABLED);
    }

    public function getRecentDocsValue(): int
    {
        return (int) ($this->pdProtectHelper->getStoreConfig(self::PATH_RECENT_VALUE) ?: 1);
    }

    public function getRecentDocsUnit(): string
    {
        return (string) ($this->pdProtectHelper->getStoreConfig(self::PATH_RECENT_UNIT) ?: 'years');
    }

    public function getRecentDocsThresholdDays(): int
    {
        return $this->unitsToDays($this->getRecentDocsValue(), $this->getRecentDocsUnit());
    }

    // ── Order-status deletion ─────────────────────────────────

    public function isOrderStatusDeletionEnabled(): bool
    {
        return (bool) $this->pdProtectHelper->getStoreConfig(self::PATH_ORDER_ENABLED);
    }

    public function getOrderStatuses(): array
    {
        $raw = (string) $this->pdProtectHelper->getStoreConfig(self::PATH_ORDER_STATUSES);
        if ($raw === '') {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $raw)));
    }

    public function getOrderAction(): string
    {
        return (string) ($this->pdProtectHelper->getStoreConfig(self::PATH_ORDER_ACTION) ?: 'anonymize');
    }

    // ── Shared utilities ──────────────────────────────────────

    private function unitsToDays(int $value, string $unit): int
    {
        return match ($unit) {
            'days'   => $value,
            'months' => $value * 30,
            'years'  => $value * 365,
            default  => $value * 365,
        };
    }
}
