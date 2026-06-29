<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

class SkipTrialAction extends Action
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

        // Always mark as skipped — this advances the state machine to profile.phtml
        $this->helper->setTrialSkipped(true);

        if ($this->helper->isTrialActive()) {
            return $result->setData([
                'success'        => true,
                'message'        => 'Trial is already active.',
                'days_remaining' => $this->helper->getTrialDaysRemaining(),
            ]);
        }

        return $result->setData([
            'success'        => true,
            'message'        => 'Trial activated! You have ' . $this->helper->getTrialDaysRemaining() . ' days remaining.',
            'days_remaining' => $this->helper->getTrialDaysRemaining(),
        ]);
    }
}
