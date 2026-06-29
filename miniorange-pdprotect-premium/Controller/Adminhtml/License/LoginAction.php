<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtectPremium\Helper\Curl;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;
use MiniOrange\PDProtectPremium\Helper\TrackingService;

class LoginAction extends Action
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
        $result   = $this->jsonFactory->create();
        $email    = (string) $this->getRequest()->getParam('email', '');
        $password = (string) $this->getRequest()->getParam('password', '');

        if (empty($email) || empty($password)) {
            return $result->setData(['success' => false, 'message' => 'Email and password are required.']);
        }

        $response = $this->curl->loginCustomer($email, $password);

        if (($response['status'] ?? '') !== 'SUCCESS') {
            $msg = $response['message'] ?? 'Login failed. Please check your credentials.';
            $this->messageManager->addErrorMessage($msg);
            return $result->setData(['success' => false, 'message' => $msg]);
        }

        // Mirrors SSO LoginExistingUserAction: stores id, apiKey (and token in SSO)
        $this->helper->setCustomerEmail($email);

        $ckl = (string) ($response['id'] ?? '');          // SSO: $customerKey['id']
        if ($ckl) {
            $this->helper->setOAuthCkl($ckl);
        }

        $apiKey = (string) ($response['apiKey'] ?? '');   // SSO: $customerKey['apiKey'] → used in ccl/vml auth headers
        if ($apiKey) {
            $this->helper->setOAuthApiKey($apiKey);
        }

        // Mirrors TwoFA LoginExistingUserAction: send miniorangeAccountEmail on successful login
        $this->trackingService->trackEvent('admin_login', [
            'miniorangeAccountEmail' => $email,
        ]);

        $this->messageManager->addSuccessMessage('Logged in successfully.');

        return $result->setData(['success' => true, 'message' => 'Logged in successfully.']);
    }
}
