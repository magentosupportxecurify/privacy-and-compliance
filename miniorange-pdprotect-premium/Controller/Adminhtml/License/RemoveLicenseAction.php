<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

class RemoveLicenseAction extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtectPremium::account';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly PremiumHelper $helper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $this->helper->clearLicenseData();
        $this->messageManager->addSuccessMessage('License removed. The module has reverted to trial mode.');

        return $result->setData([
            'success' => true,
            'message' => 'License removed. The module has reverted to trial mode.',
        ]);
    }
}
