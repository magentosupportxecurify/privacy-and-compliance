<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\License;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtectPremium\Helper\Curl;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Remove Account action — mirrors SSO LKAction::removeAccount() (mclv branch).
 *
 * Flow:
 *   1. Read the stored license key + customer credentials BEFORE clearing anything.
 *   2. If a license key exists, call updateLicenseStatus() (backupcode/updatestatus) to free
 *      the license seat on the miniOrange server — best-effort, identical to SSO's mclv branch
 *      which calls mius() unconditionally and always clears local data regardless of API result.
 *   3. Clear ALL local data so the state machine routes correctly on next login:
 *      - clearLicenseData() wipes OAUTH_LK → isLicenseVerified() returns false
 *      - With email also cleared: state machine → verify.phtml (login page)
 *      - After re-login: email set, OAUTH_LK empty, TRIAL_SKIPPED false → verifylk.phtml ✓
 */
class LogoutAction extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtectPremium::account';

    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly PremiumHelper $helper,
        private readonly Curl $curl
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        // 1. Capture credentials BEFORE clearing — needed for the updatestatus API call.
        $lk     = $this->helper->getOAuthLk();
        $ckl    = $this->helper->getOAuthCkl();
        $apiKey = $this->helper->getOAuthApiKey();

        // 2. Free the license seat on the miniOrange server (best-effort).
        //    Mirrors SSO mclv branch: mius() is called unconditionally; local data is always
        //    cleared afterwards regardless of the API response.
        if ($lk !== '' && $ckl !== '' && $apiKey !== '') {
            try {
                $this->curl->updateLicenseStatus($ckl, $apiKey, $lk);
            } catch (\Throwable) {
                // Best-effort — never block logout on a network failure.
            }
        }

        // 3. Clear ALL local account + license data.
        //    clearLicenseData() wipes: OAUTH_LK, OAUTH_CKL, LICENSE_PLAN,
        //    LICENSE_SYNC_DATE, LICENSE_EXPIRY_DATE.
        //    The remaining fields (email, api_key, trial_skipped) are cleared below.
        $this->helper->clearLicenseData();
        $this->helper->setCustomerEmail('');
        $this->helper->setOAuthApiKey('');
        $this->helper->setTrialSkipped(false);

        $this->messageManager->addSuccessMessage('Your miniOrange account has been removed successfully.');

        return $result->setData([
            'success' => true,
            'message' => 'Logged out of miniOrange account.',
        ]);
    }
}
