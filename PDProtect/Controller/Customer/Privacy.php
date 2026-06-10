<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;

class Privacy implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly CustomerSession $customerSession,
        private readonly RedirectFactory $redirectFactory,
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account/login');
        }

        $tabName = (string) ($this->scopeConfig->getValue('pdprotect/customer_privacy/tab_name', ScopeInterface::SCOPE_STORE) ?: 'Privacy Settings');

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__($tabName));
        return $page;
    }
}
