<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model\DataDeletion;

use Magento\Framework\App\ResourceConnection;
use MiniOrange\PDProtect\Logger\Logger;
use MiniOrange\PDProtect\Model\PersonalDataAnonymizer;
use MiniOrange\PDProtectPremium\Model\Config\DataDeletionConfig;

/**
 * Anonymizes or deletes orders that match configured statuses.
 * Action is controlled by the `order_action` config value ('anonymize' or 'delete').
 * Runs as part of the premium weekly data-deletion cron.
 */
class OrderStatusProcessor
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly PersonalDataAnonymizer $anonymizer,
        private readonly DataDeletionConfig $config,
        private readonly Logger $logger
    ) {}

    public function execute(): int
    {
        if (!$this->config->isOrderStatusDeletionEnabled()) {
            return 0;
        }

        $statuses = $this->config->getOrderStatuses();
        if (empty($statuses)) {
            return 0;
        }

        $action     = $this->config->getOrderAction();
        $connection = $this->resource->getConnection();
        $orderTable = $this->resource->getTableName('sales_order');

        $orderIds = $connection->fetchCol(
            $connection->select()
                ->from($orderTable, ['entity_id'])
                ->where('status IN (?)', $statuses)
                ->limit(DataDeletionConfig::BATCH_LIMIT_ORDERS)
        );

        if (empty($orderIds)) {
            return 0;
        }

        $processed = 0;

        if ($action === 'delete') {
            $processed = $connection->delete($orderTable, ['entity_id IN (?)' => $orderIds]);
            $connection->delete(
                $this->resource->getTableName('sales_order_grid'),
                ['entity_id IN (?)' => $orderIds]
            );
        } else {
            // Default: anonymize
            $processed = $this->anonymizer->anonymizeOrdersByIds($orderIds);
        }

        $this->logger->debug(sprintf(
            'OrderStatusProcessor: %s %d order(s) with status in [%s].',
            $action,
            $processed,
            implode(', ', $statuses)
        ));

        return $processed;
    }
}
