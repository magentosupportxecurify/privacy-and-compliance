<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Cron;

use MiniOrange\PDProtect\Logger\Logger;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;
use MiniOrange\PDProtectPremium\Model\DataDeletion\AbandonedCustomerProcessor;
use MiniOrange\PDProtectPremium\Model\DataDeletion\OrderStatusProcessor;
use MiniOrange\PDProtectPremium\Model\DataDeletion\RecentDocumentsProcessor;

/**
 * Weekly premium cron job that orchestrates all automatic data-deletion processors.
 * Scheduled: Sunday 02:00 (0 2 * * 0) via etc/crontab.xml.
 */
class RunDataDeletion
{
    public function __construct(
        private readonly AbandonedCustomerProcessor $abandonedProcessor,
        private readonly RecentDocumentsProcessor $recentDocsProcessor,
        private readonly OrderStatusProcessor $orderStatusProcessor,
        private readonly Logger $logger,
        private readonly PremiumHelper $premiumHelper
    ) {}

    public function execute(): void
    {
        if (!$this->premiumHelper->isModuleActive()) {
            $this->logger->debug('RunDataDeletion cron: module inactive (trial expired / no license), skipping.');
            return;
        }

        $this->logger->debug('RunDataDeletion cron: starting.');

        try {
            $abandoned = $this->abandonedProcessor->execute();
            $this->logger->debug(sprintf('RunDataDeletion: abandoned processor removed %d customer(s).', $abandoned));
        } catch (\Throwable $e) {
            $this->logger->debug('RunDataDeletion: abandoned processor error: ' . $e->getMessage());
        }

        try {
            $docs = $this->recentDocsProcessor->execute();
            $this->logger->debug(sprintf('RunDataDeletion: recent-docs processor removed %d document(s).', $docs));
        } catch (\Throwable $e) {
            $this->logger->debug('RunDataDeletion: recent-docs processor error: ' . $e->getMessage());
        }

        try {
            $orders = $this->orderStatusProcessor->execute();
            $this->logger->debug(sprintf('RunDataDeletion: order-status processor processed %d order(s).', $orders));
        } catch (\Throwable $e) {
            $this->logger->debug('RunDataDeletion: order-status processor error: ' . $e->getMessage());
        }

        $this->logger->debug('RunDataDeletion cron: complete.');
    }
}
