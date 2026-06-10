<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Adminhtml\GeneralSettings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtect::general_settings';

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

            $this->configWriter->save('pdprotect/general/display_popup', isset($post['display_popup']) ? '1' : '0', 'default', 0);
            $mode = in_array($post['allowed_countries_mode'] ?? '', ['all', 'none', 'specific'], true)
                ? $post['allowed_countries_mode']
                : 'all';
            $this->configWriter->save('pdprotect/general/allowed_countries_mode', $mode, 'default', 0);
            $this->configWriter->save('pdprotect/general/country_restriction', implode(',', (array) ($post['country_restriction'] ?? [])), 'default', 0);
            $this->configWriter->save('pdprotect/general/log_guest_consent', isset($post['log_guest_consent']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/general/log_auto_clean', isset($post['log_auto_clean']) ? '1' : '0', 'default', 0);
            $this->configWriter->save('pdprotect/general/log_auto_clean_period', (string) ($post['log_auto_clean_period'] ?? '30'), 'default', 0);
            $this->configWriter->save('pdprotect/general/log_auto_clean_unit', (string) ($post['log_auto_clean_unit'] ?? 'days'), 'default', 0);

            $this->dataHelper->flushCache('GeneralSettings\Index');

            $this->messageManager->addSuccessMessage(__('General settings saved successfully.'));
            $this->dataHelper->reinitConfig();

            return $this->resultRedirectFactory->create()->setPath('mopdp/generalsettings/index');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Personal Data Protection'));
        return $page;
    }
}
