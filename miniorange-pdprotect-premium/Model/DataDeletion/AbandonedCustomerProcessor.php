<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model\DataDeletion;

use Magento\Framework\App\ResourceConnection;
use MiniOrange\PDProtect\Logger\Logger;
use MiniOrange\PDProtect\Model\CustomerDataEraser;
use MiniOrange\PDProtectPremium\Model\Config\DataDeletionConfig;

/**
 * Erases customer accounts that have had no orders placed after a configurable cutoff date.
 * Runs as part of the premium weekly data-deletion cron.
 */
class AbandonedCustomerProcessor
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly CustomerDataEraser $dataEraser,
        private readonly DataDeletionConfig $config,
        private readonly Logger $logger
    ) {}

    public function execute(): int
    {
        if (!$this->config->isAbandonedDeletionEnabled()) {
            return 0;
        }

        $cutoff  = $this->config->getCutoffDate($this->config->getAbandonedThresholdDays());
        $connection = $this->resource->getConnection();

        // Find customers whose latest order (or registration if no orders) is before the cutoff.
        $customerTable = $this->resource->getTableName('customer_entity');
        $orderTable    = $this->resource->getTableName('sales_order');

        $select = $connection->select()
            ->from(['c' => $customerTable], ['entity_id'])
            ->joinLeft(
                ['o' => $orderTable],
                'o.customer_id = c.entity_id',
                []
            )
            ->group('c.entity_id')
            ->having('MAX(COALESCE(o.created_at, c.created_at)) < ?', $cutoff)
            ->limit(DataDeletionConfig::BATCH_LIMIT);

        $customerIds = $connection->fetchCol($select);

        $deleted = 0;
        foreach ($customerIds as $customerId) {
            try {
                $this->dataEraser->execute((int) $customerId);
                $deleted++;
            } catch (\Throwable $e) {
                $this->logger->debug(
                    'AbandonedCustomerProcessor: failed for customer ' . $customerId . ': ' . $e->getMessage()
                );
            }
        }

        $this->logger->debug(sprintf('AbandonedCustomerProcessor: deleted %d abandoned customer(s).', $deleted));
        return $deleted;
    }
}
