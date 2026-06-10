<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_CookieConsent::config';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Cookie Consent - GDPR, DPDP, CCPA'));

        return $page;
    }

    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed('MiniOrange_CookieConsent::config')) {
            return true;
        }

        $tab = (string) $this->getRequest()->getParam('active_tab', 'banner_settings');

        return $this->_authorization->isAllowed(self::aclResourceForTab($tab));
    }

    public static function aclResourceForTab(string $tab): string
    {
        return match ($tab) {
            'script_manager' => 'MiniOrange_CookieConsent::tab_script_manager',
            'upgrade' => 'MiniOrange_CookieConsent::tab_upgrade',
            default => 'MiniOrange_CookieConsent::tab_banner_settings',
        };
    }
}
