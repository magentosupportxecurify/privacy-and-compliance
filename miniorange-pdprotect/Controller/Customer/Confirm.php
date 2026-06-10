<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;

class Confirm implements HttpGetActionInterface
{
    private const VALID_ACTIONS = ['download', 'anonymize', 'delete', 'withdraw'];

    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly CustomerSession $customerSession,
        private readonly RedirectFactory $redirectFactory
    ) {}

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Confirm Action'));
        return $page;
    }
}
