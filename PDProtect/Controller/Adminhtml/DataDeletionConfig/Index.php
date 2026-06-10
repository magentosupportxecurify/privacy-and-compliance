<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Adminhtml\DataDeletionConfig;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Free version: render premium-only notice page. No form saving.
 * Premium overrides this via route priority (before="MiniOrange_PDProtect").
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtect::datadeletionconfig';

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
