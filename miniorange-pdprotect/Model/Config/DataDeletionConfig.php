<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Stub for the free module. All data-deletion features are disabled in the free tier.
 * The premium module provides the full implementation via DI preference.
 */
class DataDeletionConfig
{
    public const BATCH_LIMIT = 100;
    public const BATCH_LIMIT_QUOTES = 200;
    public const BATCH_LIMIT_ORDERS = 200;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    public function isAbandonedDeletionEnabled(): bool { return false; }
    public function getAbandonedValue(): int { return 2; }
    public function getAbandonedUnit(): string { return 'years'; }
    public function getAbandonedThresholdDays(): int { return 730; }

    public function isRecentDocsDeletionEnabled(): bool { return false; }
    public function getRecentDocsValue(): int { return 1; }
    public function getRecentDocsUnit(): string { return 'years'; }
    public function getRecentDocsThresholdDays(): int { return 365; }

    public function isOrderStatusDeletionEnabled(): bool { return false; }
    public function getOrderStatuses(): array { return []; }
    public function getOrderAction(): string { return 'anonymize'; }

    public function getCutoffDate(int $thresholdDays): string
    {
        return (new \DateTimeImmutable('-' . $thresholdDays . ' days'))->format('Y-m-d H:i:s');
    }
}
