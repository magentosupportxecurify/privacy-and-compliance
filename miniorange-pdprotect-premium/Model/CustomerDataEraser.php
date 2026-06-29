<?php
declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtect\Model\CustomerDataEraser as FreeCustomerDataEraser;

class CustomerDataEraser extends FreeCustomerDataEraser
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ResourceConnection $resource,
        private readonly PersonalDataAnonymizer $personalDataAnonymizer,
        private readonly Registry $registry,
        private readonly Data $dataHelper
    ) {
    }

    /**
     * Anonymize order data, delete consent log, then delete the customer account.
     *
     * Sets the 'isSecureArea' registry flag before calling customerRepository->deleteById()
     * because Magento's Customer model checks this flag (not State::getAreaCode()) to
     * determine whether a delete is permitted. Without it, cron and CLI contexts receive
     * "Delete operation is forbidden for current area".
     *
     * @throws \Exception
     */
    public function execute(int $customerId): void
    {
        try {
            $this->personalDataAnonymizer->anonymizeCustomerOrders($customerId);

            $this->resource->getConnection()->delete(
                $this->resource->getTableName('miniorange_pdprotect_consent_log'),
                ['customer_id = ?' => $customerId]
            );

            // Register as secure area so the customer model permits deletion from
            // non-frontend/non-adminhtml contexts (cron, CLI).
            // The third argument (true) prevents an exception if already registered.
            $this->registry->register('isSecureArea', true, true);
            try {
                $this->customerRepository->deleteById($customerId);
            } finally {
                $this->registry->unregister('isSecureArea');
            }
        } catch (\Exception $e) {
            $this->dataHelper->log_debug(
                'CustomerDataEraser: failed to erase customer ' . $customerId . ': ' . $e->getMessage()
            );
            throw $e;
        }
    }
}
