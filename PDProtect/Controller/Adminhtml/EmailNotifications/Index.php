<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Adminhtml\EmailNotifications;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtect::email_notifications';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Personal Data Protection'));
        return $page;
    }
}
