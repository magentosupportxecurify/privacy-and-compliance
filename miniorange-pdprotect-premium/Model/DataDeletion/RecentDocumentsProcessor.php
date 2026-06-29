<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model\DataDeletion;

use Magento\Framework\App\ResourceConnection;
use MiniOrange\PDProtect\Logger\Logger;
use MiniOrange\PDProtectPremium\Model\Config\DataDeletionConfig;

/**
 * Removes stale quote data for guest carts and registered customers
 * that have not been active after a configurable cutoff date.
 * Runs as part of the premium weekly data-deletion cron.
 */
class RecentDocumentsProcessor
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly DataDeletionConfig $config,
        private readonly Logger $logger
    ) {}

    public function execute(): int
    {
        if (!$this->config->isRecentDocsDeletionEnabled()) {
            return 0;
        }

        $cutoff     = $this->config->getCutoffDate($this->config->getRecentDocsThresholdDays());
        $connection = $this->resource->getConnection();
        $quoteTable = $this->resource->getTableName('quote');

        // Delete expired quotes (both guest and registered) in batches.
        $deleted = 0;
        do {
            $ids = $connection->fetchCol(
                $connection->select()
                    ->from($quoteTable, ['entity_id'])
                    ->where('updated_at < ?', $cutoff)
                    ->limit(DataDeletionConfig::BATCH_LIMIT_QUOTES)
            );

            if (empty($ids)) {
                break;
            }

            $deleted += $connection->delete($quoteTable, ['entity_id IN (?)' => $ids]);
        } while (count($ids) === DataDeletionConfig::BATCH_LIMIT_QUOTES);

        $this->logger->debug(sprintf('RecentDocumentsProcessor: deleted %d stale quote(s).', $deleted));
        return $deleted;
    }
}
