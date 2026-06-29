<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\DataDeletionConfig;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override: saves data-deletion configuration.
 * Takes priority over the free stub via routes.xml before="MiniOrange_PDProtect".
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtectPremium::datadeletionconfig';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly PDProtectHelper $dataHelper,
        private readonly PremiumHelper $premiumHelper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            if (!$this->premiumHelper->isModuleActive()) {
                $this->messageManager->addErrorMessage(__('Please activate your license to use this feature.'));
                return $this->resultRedirectFactory->create()->setPath('mopdp/datadeletionconfig/index');
            }
            $post = $this->getRequest()->getPostValue();

            $this->dataHelper->setStoreConfig('mopdp/data_deletion/abandoned_enabled', isset($post['abandoned_enabled']) ? '1' : '0');
            $this->dataHelper->setStoreConfig('mopdp/data_deletion/abandoned_value', (string) ((int) ($post['abandoned_value'] ?? 2)));
            $unit = in_array($post['abandoned_unit'] ?? '', ['days', 'months', 'years'], true) ? $post['abandoned_unit'] : 'years';
            $this->dataHelper->setStoreConfig('mopdp/data_deletion/abandoned_unit', $unit);

            $this->dataHelper->setStoreConfig('mopdp/data_deletion/recent_docs_enabled', isset($post['recent_docs_enabled']) ? '1' : '0');
            $this->dataHelper->setStoreConfig('mopdp/data_deletion/recent_docs_value', (string) ((int) ($post['recent_docs_value'] ?? 1)));
            $rUnit = in_array($post['recent_docs_unit'] ?? '', ['days', 'months', 'years'], true) ? $post['recent_docs_unit'] : 'years';
            $this->dataHelper->setStoreConfig('mopdp/data_deletion/recent_docs_unit', $rUnit);

            $this->dataHelper->setStoreConfig('mopdp/data_deletion/order_status_enabled', isset($post['order_status_enabled']) ? '1' : '0');
            $statuses = implode(',', array_filter(array_map('trim', (array) ($post['order_statuses'] ?? []))));
            $this->dataHelper->setStoreConfig('mopdp/data_deletion/order_statuses', $statuses);
            $action = in_array($post['order_action'] ?? '', ['anonymize', 'delete'], true) ? $post['order_action'] : 'anonymize';
            $this->dataHelper->setStoreConfig('mopdp/data_deletion/order_action', $action);

            $this->dataHelper->flushCache('DataDeletionConfig\Index');
            $this->messageManager->addSuccessMessage(__('Data Deletion & Anonymization settings saved successfully.'));
            $this->dataHelper->reinitConfig();

            return $this->resultRedirectFactory->create()->setPath('mopdp/datadeletionconfig/index');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Personal Data Protection'));
        return $page;
    }
}
