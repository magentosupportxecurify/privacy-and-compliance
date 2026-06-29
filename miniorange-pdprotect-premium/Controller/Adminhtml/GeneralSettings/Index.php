<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\GeneralSettings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override of PDProtect\Controller\Adminhtml\GeneralSettings\Index.
 *
 * Blocks POST saves when the module is not active (trial expired + no license),
 * so that removing the 'disabled' attribute via browser DevTools cannot bypass
 * the license enforcement.
 */
class Index extends \MiniOrange\PDProtect\Controller\Adminhtml\GeneralSettings\Index
{
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        PDProtectHelper $dataHelper,
        private readonly PremiumHelper $premiumHelper
    ) {
        parent::__construct($context, $pageFactory, $dataHelper);
    }

    public function execute()
    {
        if ($this->getRequest()->isPost() && !$this->premiumHelper->isModuleActive()) {
            $this->messageManager->addErrorMessage(__('Please activate your license to use this feature.'));
            return $this->resultRedirectFactory->create()->setPath('mopdp/generalsettings/index');
        }

        return parent::execute();
    }
}
