<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MiniOrange\PDProtectPremium\Helper\Constants;
use MiniOrange\PDProtectPremium\Helper\Curl;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;
use MiniOrange\PDProtectPremium\Helper\TrackingService;

/**
 * Validates and activates a license key.
 *
 * Uses a standard form POST + redirect pattern (mirrors SSO LKAction) so that
 * error messages appear via Magento's standard admin flash-message banner at the
 * top of the page, not inline in the form.
 */
class LKAction extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtectPremium::account';

    public function __construct(
        Context $context,
        private readonly Curl $curl,
        private readonly PremiumHelper $helper,
        private readonly TrackingService $trackingService,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        // Always redirect back to the referring page (the account tab)
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setRefererUrl();

        $lk      = trim((string) $this->getRequest()->getParam('lk', ''));
        $email   = $this->helper->getCustomerEmail();
        $ckl     = $this->helper->getOAuthCkl();       // customer key — stored as 'id' from login response
        $apiKey  = $this->helper->getOAuthApiKey();    // customer API key — stored as 'apiKey' from login response
        $baseUrl = (string) ($this->scopeConfig->getValue('web/unsecure/base_url') ?? '');

        if (empty($lk)) {
            $this->messageManager->addErrorMessage('License key is required.');
            return $redirect;
        }
        if (empty($email)) {
            $this->messageManager->addErrorMessage('Please log in to your miniOrange account first.');
            return $redirect;
        }

        // ── Step 1: ccl() — confirm customer has a plan ──────────────────────
        // Mirrors SSO execute(): loop plans until ccl() returns SUCCESS
        $cclResult    = null;
        $detectedPlan = null;
        foreach (Constants::PLAN_APP_NAMES as $appName) {
            $resp = $this->curl->checkCustomerLicense($ckl, $apiKey, $appName);
            if (($resp['status'] ?? '') === 'SUCCESS') {
                $cclResult    = $resp;
                $detectedPlan = $appName;
                break;
            }
        }

        if ($detectedPlan === null) {
            // Mirrors SSO _vlk_fail() — exact NOT_UPGRADED_YET message
            $this->messageManager->addErrorMessage(
                'You have not upgraded yet. Please update as per the necessary plan..'
            );
            return $redirect;
        }

        // ── Step 2: vml() — validate the actual license key ──────────────────
        // Mirrors SSO _vlk_success()
        $vmlResponse = $this->curl->validateLicenseKey($lk, $ckl, $apiKey, $baseUrl);

        // isExpired=true → LICENSE_KEY_IN_USE (checked first, per SSO)
        if (!empty($vmlResponse['isExpired'])) {
            $this->messageManager->addErrorMessage(
                'License key you have entered has already been used. Please enter a key which has not been used'
                . ' before on any other instance or if you have exhausted all your keys then contact us at'
                . ' miniOrange info@xecurify.com to buy more keys.'
            );
            return $redirect;
        }

        // Content not array / no status → ENTERED_INVALID_KEY
        if (!is_array($vmlResponse) || empty($vmlResponse['status'])) {
            $this->messageManager->addErrorMessage(
                'You have entered an invalid license key. Please enter a valid license key.'
            );
            return $redirect;
        }

        $vmlStatus = strtoupper($vmlResponse['status']);

        if ($vmlStatus === 'SUCCESS') {
            $plan = Constants::PLAN_PREMIUM;
            $this->helper->setOAuthLk($lk);
            $this->helper->setLicensePlan($plan);

            // Store sync date (current time) and expiry from vml response — mirrors SSO LKAction
            $this->helper->setLicenseSyncDate(date('Y-m-d H:i:s'));
            if (!empty($vmlResponse['licenseExpiry'])) {
                $this->helper->setLicenseExpiryDate((string) $vmlResponse['licenseExpiry']);
            }

            if (!$this->helper->isPlanActivationTracked()) {
                $this->trackingService->trackPlanActivation($plan);
            }

            $this->messageManager->addSuccessMessage('Your license has been verified. You can now use all premium features.');
            return $redirect;
        }

        if ($vmlStatus === 'FAILED') {
            // 'Code has Expired' → LICENSE_KEY_IN_USE; anything else → ENTERED_INVALID_KEY
            if (strcasecmp($vmlResponse['message'] ?? '', 'Code has Expired') === 0) {
                $this->messageManager->addErrorMessage(
                    'License key you have entered has already been used. Please enter a key which has not been used'
                    . ' before on any other instance or if you have exhausted all your keys then contact us at'
                    . ' miniOrange info@xecurify.com to buy more keys.'
                );
            } else {
                $this->messageManager->addErrorMessage(
                    'You have entered an invalid license key. Please enter a valid license key.'
                );
            }
            return $redirect;
        }

        // Any other status → ERROR_OCCURRED
        $this->messageManager->addErrorMessage(
            'An error occured while processing your request. Please try again.'
        );
        return $redirect;
    }
}
