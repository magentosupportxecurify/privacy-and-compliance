<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Mirrors SSO's activatePremiumPlan() — resets TRIAL_SKIPPED to false so
 * the account.phtml state machine routes back to verifylk.phtml on next page load.
 */
class ActivatePremiumPlanAction extends Action
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
        $this->helper->setTrialSkipped(false);
        return $this->jsonFactory->create()->setData(['success' => true]);
    }
}
