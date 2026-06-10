<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Adminhtml\CustomerPrivacy;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtect::customer_privacy';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly WriterInterface $configWriter,
        private readonly PDProtectHelper $dataHelper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPostValue();

            $this->configWriter->save('pdprotect/customer_privacy/tab_name', (string) ($post['tab_name'] ?? 'Privacy Settings'), 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/show_privacy_policy', isset($post['show_privacy_policy']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/privacy_policy_url', (string) ($post['privacy_policy_url'] ?? ''), 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/show_cookie_policy', isset($post['show_cookie_policy']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/cookie_policy_url', (string) ($post['cookie_policy_url'] ?? ''), 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/enable_data_download', isset($post['enable_data_download']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/enable_anonymize', isset($post['enable_anonymize']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/enable_delete_account', isset($post['enable_delete_account']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/enable_opt_out', isset($post['enable_opt_out']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/enable_dpo_info', isset($post['enable_dpo_info']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/dpo_name', (string) ($post['dpo_name'] ?? ''), 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/dpo_email', (string) ($post['dpo_email'] ?? ''), 'default', 0);
            $this->configWriter->save('pdprotect/customer_privacy/dpo_phone', (string) ($post['dpo_phone'] ?? ''), 'default', 0);

            $this->dataHelper->flushCache('CustomerPrivacy\Index');

            $this->messageManager->addSuccessMessage(__('Customer Account Privacy settings saved successfully.'));
            $this->dataHelper->reinitConfig();

            return $this->resultRedirectFactory->create()->setPath('mopdp/customerprivacy/index');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Personal Data Protection'));
        return $page;
    }
}
