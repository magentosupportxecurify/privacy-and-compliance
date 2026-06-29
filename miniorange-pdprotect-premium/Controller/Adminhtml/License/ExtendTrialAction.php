<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;
use MiniOrange\PDProtectPremium\Helper\TrackingService;

class ExtendTrialAction extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtectPremium::account';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly PremiumHelper $helper,
        private readonly TrackingService $trackingService
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if ($this->helper->isTrialExtended()) {
            return $result->setData(['success' => false, 'message' => 'Trial has already been extended once.']);
        }

        if (!$this->helper->getTrialStartDate()) {
            return $result->setData(['success' => false, 'message' => 'No active trial found.']);
        }

        $this->helper->extendTrial();
        $this->trackingService->trackTrialExtended();
        $this->messageManager->addSuccessMessage('Your trial has been extended by 7 days.');

        return $result->setData([
            'success'       => true,
            'message'       => 'Your trial has been extended by 7 days.',
            'days_remaining' => $this->helper->getTrialDaysRemaining(),
        ]);
    }
}
