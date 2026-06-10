<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use MiniOrange\PDProtect\Helper\Data;

class ConsentLogCleaner
{
    private const TABLE = 'miniorange_pdprotect_consent_log';

    // Free tier: always clean logs older than 1 day; period/unit config is premium-only.
    private const FREE_PERIOD_DAYS = 1;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ResourceConnection $resource,
        private readonly Data $dataHelper
    ) {}

    public function execute(): void
    {
        // Free tier: always clean — the log_auto_clean toggle is locked/disabled in the admin UI.
        // The 1-day retention is hardcoded; period/unit config is premium-only.
        $cutoff = (new \DateTimeImmutable())->sub(new \DateInterval('P' . self::FREE_PERIOD_DAYS . 'D'))->format('Y-m-d H:i:s');

        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName(self::TABLE);

        $deleted = $connection->delete($table, ['created_at < ?' => $cutoff]);

        if ($deleted > 0) {
            $this->dataHelper->log_debug(
                sprintf('ConsentLogCleaner: deleted %d log row(s) older than %d day(s) (cutoff: %s).', $deleted, self::FREE_PERIOD_DAYS, $cutoff)
            );
        }
    }
}
