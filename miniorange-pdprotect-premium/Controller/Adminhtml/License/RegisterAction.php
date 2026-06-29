<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtectPremium\Helper\Curl;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;
use MiniOrange\PDProtectPremium\Helper\TrackingService;

class RegisterAction extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtectPremium::account';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly Curl $curl,
        private readonly PremiumHelper $helper,
        private readonly TrackingService $trackingService
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $email  = (string) $this->getRequest()->getParam('email', '');
        $phone  = (string) $this->getRequest()->getParam('phone', '');

        if (empty($email)) {
            return $result->setData(['success' => false, 'message' => 'Email is required.']);
        }

        $response = $this->curl->registerCustomer($email, $phone);

        if (($response['status'] ?? '') !== 'SUCCESS') {
            $msg = $response['message'] ?? 'Registration failed. Please try again.';
            return $result->setData(['success' => false, 'message' => $msg]);
        }

        $this->helper->setCustomerEmail($email);

        // Start trial on first registration
        if (!$this->helper->getTrialStartDate()) {
            $this->helper->setTrialStartDate(date('Y-m-d H:i:s'));
            $this->helper->setInstallationDate(date('Y-m-d H:i:s'));
            $this->trackingService->trackTrialInstall();
        }

        return $result->setData(['success' => true, 'message' => 'Registration successful. Check your email for the OTP.']);
    }
}
