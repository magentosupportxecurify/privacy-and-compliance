<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\ViewModel\Adminhtml;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\UrlInterface;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

class Account implements ArgumentInterface
{
    public function __construct(
        private readonly PremiumHelper $helper,
        private readonly UrlInterface $urlBuilder
    ) {}

    public function isLicenseVerified(): bool
    {
        return $this->helper->isLicenseVerified();
    }

    public function getLicensePlan(): string
    {
        return $this->helper->getLicensePlan();
    }

    public function getCustomerEmail(): string
    {
        return $this->helper->getCustomerEmail();
    }

    public function isTrialActive(): bool
    {
        return $this->helper->isTrialActive();
    }

    public function isTrialExpired(): bool
    {
        return $this->helper->isTrialExpired();
    }

    public function getTrialDaysRemaining(): int
    {
        return $this->helper->getTrialDaysRemaining();
    }

    public function isTrialExtended(): bool
    {
        return $this->helper->isTrialExtended();
    }

    public function isTrialSkipped(): bool
    {
        return $this->helper->isTrialSkipped();
    }

    public function getCustomerKey(): string
    {
        return $this->helper->getOAuthCkl();
    }

    public function getActivatePremiumPlanUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/activatepremiumplanaction');
    }

    public function getLoginUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/loginaction');
    }

    public function getRegisterUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/registeraction');
    }

    public function getValidateLkUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/lkaction');
    }

    public function getExtendTrialUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/extendtrialaction');
    }

    public function getRemoveLicenseUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/removelicenseaction');
    }

    public function getSkipTrialUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/skiptrialaction');
    }

    public function getLogoutUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/license/logoutaction');
    }

    public function getSupportUrl(): string
    {
        return $this->urlBuilder->getUrl('mopdp/support/submitquery');
    }

    /** Date/time the license was last synced — stored on successful vml() (mirrors SSO getLicenseSyncDate). */
    public function getLicenseSyncDate(): ?string
    {
        return $this->helper->getLicenseSyncDate();
    }

    /** License expiry date from the vml API response — stored on successful activation. */
    public function getLicenseExpiryDate(): ?string
    {
        return $this->helper->getLicenseExpiryDate();
    }
}
