<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\DataDeletion;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use MiniOrange\PDProtect\Model\CustomerDataEraser;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override: no lifetime approval cap.
 * Overrides the free controller via DI preference in etc/di.xml.
 */
class UpdateStatus extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtect::data_deletion';

    public function __construct(
        Context $context,
        private readonly ResourceConnection $resource,
        private readonly CustomerDataEraser $dataEraser,
        private readonly PremiumHelper $premiumHelper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->premiumHelper->isModuleActive()) {
            $this->messageManager->addErrorMessage(__('Please activate your license to use this feature.'));
            return $this->resultRedirectFactory->create()->setPath('mopdp/datadeletion/index');
        }

        $requestId = (int) $this->getRequest()->getParam('request_id');
        $status    = (string) $this->getRequest()->getParam('status');
        $adminNote = (string) $this->getRequest()->getParam('admin_note', '');

        if (!in_array($status, ['approved', 'rejected'], true) || $requestId <= 0) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $this->resultRedirectFactory->create()->setPath('mopdp/datadeletion/index');
        }

        $connection = $this->resource->getConnection();
        $table      = $this->resource->getTableName('miniorange_pdprotect_deletion_request');

        $row = $connection->fetchRow(
            "SELECT customer_id FROM {$table} WHERE request_id = ?",
            [$requestId]
        );

        if (!$row) {
            $this->messageManager->addErrorMessage(__('Deletion request not found.'));
            return $this->resultRedirectFactory->create()->setPath('mopdp/datadeletion/index');
        }

        if ($status === 'approved') {
            try {
                $this->dataEraser->execute((int) $row['customer_id']);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Could not delete customer data: %1', $e->getMessage())
                );
                return $this->resultRedirectFactory->create()->setPath('mopdp/datadeletion/index');
            }
        }

        $connection->update(
            $table,
            [
                'status'       => $status,
                'processed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'admin_note'   => $adminNote,
            ],
            ['request_id = ?' => $requestId]
        );

        $label = $status === 'approved' ? 'approved' : 'rejected';
        $this->messageManager->addSuccessMessage(__("Deletion request #{$requestId} has been {$label}."));
        return $this->resultRedirectFactory->create()->setPath('mopdp/datadeletion/index');
    }
}
