<?php
namespace MiniOrange\Privacy\Controller\Adminhtml\Upgrade;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    /** @var PageFactory */
    private PageFactory $pageFactory;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    /**
     * Execute upgrade page action.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('MiniOrange_Privacy::upgrade');
        $resultPage->addBreadcrumb(__('Privacy Suite'), __('Privacy Suite'));
        $resultPage->addBreadcrumb(__('Upgrade Plans'), __('Upgrade Plans'));
        $resultPage->getConfig()->getTitle()->prepend(__('Upgrade Plans'));
        return $resultPage;
    }

    /**
     * Check if action is allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MiniOrange_Privacy::upgrade');
    }
}
