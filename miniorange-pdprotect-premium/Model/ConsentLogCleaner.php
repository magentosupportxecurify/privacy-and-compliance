<?php
declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtect\Model\ConsentLogCleaner as FreeConsentLogCleaner;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override: honours the admin-configured period and unit
 * (pdprotect/general/log_auto_clean_period + log_auto_clean_unit)
 * instead of the free module's hardcoded 1-day retention.
 */
class ConsentLogCleaner extends FreeConsentLogCleaner
{
    private const TABLE = 'miniorange_pdprotect_consent_log';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ResourceConnection $resource,
        private readonly Data $dataHelper,
        private readonly PremiumHelper $premiumHelper
    ) {
    }

    public function execute(): void
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return;
        }

        if (!(bool) $this->scopeConfig->getValue('pdprotect/general/log_auto_clean')) {
            return;
        }

        $period = (int) ($this->scopeConfig->getValue('pdprotect/general/log_auto_clean_period') ?: 30);
        $unit   = (string) ($this->scopeConfig->getValue('pdprotect/general/log_auto_clean_unit') ?: 'days');

        $interval = match ($unit) {
            'weeks'  => new \DateInterval('P' . $period . 'W'),
            'months' => new \DateInterval('P' . $period . 'M'),
            default  => new \DateInterval('P' . $period . 'D'), // days
        };

        $cutoff     = (new \DateTimeImmutable())->sub($interval)->format('Y-m-d H:i:s');
        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName(self::TABLE);

        $deleted = $connection->delete($table, ['created_at < ?' => $cutoff]);

        $this->dataHelper->log_debug(
            sprintf(
                'ConsentLogCleaner: deleted %d log row(s) older than %d %s (cutoff: %s).',
                $deleted,
                $period,
                $unit,
                $cutoff
            )
        );
    }
}
