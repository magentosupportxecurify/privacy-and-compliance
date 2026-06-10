<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Adminhtml\Upgrade;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtect::upgrade';

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
        $page->addBreadcrumb(__('Upgrade Plans'), __('Upgrade Plans'));
        return $page;
    }
}
