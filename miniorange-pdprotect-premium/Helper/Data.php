<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Helper;

use MiniOrange\PDProtect\Helper\Encryption;

class Data extends \MiniOrange\PDProtect\Helper\Data
{
    // Constructor is identical to the base class — no extra dependencies needed.
    // getStoreConfig() and setStoreConfig() are inherited from PDProtect\Helper\Data.

    // ── Module active gate (single source of truth) ───────────

    /**
     * Returns true when the module is permitted to function:
     * either the trial is still running or a valid license key is present.
     * All feature-gate methods delegate here so there is only one place to check.
     */
    public function isModuleActive(): bool
    {
        return $this->isLicenseVerified() || $this->isTrialActive();
    }

    // ── Premium feature gates — all delegate to isModuleActive() ──

    public function isPremium(): bool
    {
        return true; // premium module IS installed
    }

    public function isPremiumVersion(): bool
    {
        return true; // premium module IS installed
    }

    public function isDataDeletionEnabled(): bool
    {
        return $this->isModuleActive();
    }

    public function isCountryFilteringEnabled(): bool
    {
        return $this->isModuleActive();
    }

    public function isAutoCleanConfigurable(): bool
    {
        return $this->isModuleActive();
    }

    public function isCustomerDataControlsFunctional(): bool
    {
        return $this->isModuleActive();
    }

    // ── Plan name ─────────────────────────────────────────────

    public function getPlanName(): string
    {
        if ($this->isLicenseVerified()) {
            return 'Personal Data Protection Premium';
        }
        return 'Personal Data Protection Trial';
    }

    // ── Delete approval: unlimited when active, fully blocked when inactive ──

    public function getDeleteApprovalLifetimeLimit(): int
    {
        return PHP_INT_MAX;
    }

    public function hasReachedDeleteLimit(): bool
    {
        return false; // premium has no approval limit
    }

    // ── Trial helpers ─────────────────────────────────────────

    public function getInstallationDate(): ?string
    {
        $v = $this->pdScopeConfig->getValue(Constants::INSTALLATION_DATE);
        return $v ? (string) $v : null;
    }

    public function setInstallationDate(string $date): void
    {
        $this->saveConfig(Constants::INSTALLATION_DATE, $date);
    }

    public function getTrialStartDate(): ?string
    {
        $v = $this->pdScopeConfig->getValue(Constants::TRIAL_START_DATE);
        if (!$v) {
            return null;
        }
        $decrypted = Encryption::decrypt((string) $v, Constants::DEFAULT_TOKEN_VALUE);
        return (strtotime($decrypted) !== false) ? $decrypted : (string) $v;
    }

    public function setTrialStartDate(string $date): void
    {
        $this->saveConfig(Constants::TRIAL_START_DATE, Encryption::encrypt($date, Constants::DEFAULT_TOKEN_VALUE));
    }

    public function isTrialActive(): bool
    {
        $start = $this->getTrialStartDate();
        if (!$start) {
            return false;
        }
        return !$this->isTrialExpired();
    }

    public function isTrialExpired(): bool
    {
        $start = $this->getTrialStartDate();
        if (!$start) {
            return false;
        }
        $trialEnd = strtotime($start) + (Constants::TRIAL_DAYS * 86400);
        return time() > $trialEnd;
    }

    public function getTrialDaysRemaining(): int
    {
        $start = $this->getTrialStartDate();
        if (!$start) {
            return 0;
        }
        $trialEnd = strtotime($start) + (Constants::TRIAL_DAYS * 86400);
        $remaining = $trialEnd - time();
        return max(0, (int) ceil($remaining / 86400));
    }

    /** Mirrors TwoFA TRIAL_PLAN_CONSTANT: true when trial tracking has been sent */
    public function isTrialPlanConstantSet(): bool
    {
        return !empty($this->pdScopeConfig->getValue(Constants::TRACKING_TRIAL_PLAN_CONSTANT));
    }

    /**
     * Returns the free module's first-visit Unix timestamp (pdprotect/tracking/timestamp).
     * Used by TrackingService to merge free+premium install dates into one tracking entry.
     * Empty string when the free module was never visited before premium was installed.
     */
    public function getFreeInstallTimestamp(): string
    {
        return (string) ($this->pdScopeConfig->getValue(Constants::TRACKING_TIMESTAMP) ?? '');
    }

    /** PDProtect-specific: prevents trial-expired event from re-firing every page load */
    public function isTrialExpiredTracked(): bool
    {
        return (bool) $this->pdScopeConfig->getValue(Constants::TRACKING_TRIAL_EXPIRED_SENT);
    }

    /** Prevents plan-activation tracking from re-firing if license is removed and re-added */
    public function isPlanActivationTracked(): bool
    {
        return !empty($this->pdScopeConfig->getValue(Constants::TRACKING_PLAN_ACTIVATION_SENT));
    }

    public function isTrialExtended(): bool
    {
        return (bool) $this->pdScopeConfig->getValue(Constants::TRIAL_EXTENDED);
    }

    public function isTrialSkipped(): bool
    {
        return (bool) $this->pdScopeConfig->getValue(Constants::TRIAL_SKIPPED);
    }

    public function setTrialSkipped(bool $val): void
    {
        $this->saveConfig(Constants::TRIAL_SKIPPED, $val ? '1' : '');
    }

    public function extendTrial(): void
    {
        $newStart = date('Y-m-d H:i:s', time() - ((Constants::TRIAL_DAYS - Constants::TRIAL_EXTENSION_DAYS) * 86400));
        $this->setTrialStartDate($newStart);
        $this->saveConfig(Constants::TRIAL_EXTENDED, '1');
    }

    // ── License helpers ───────────────────────────────────────

    public function isLicenseVerified(): bool
    {
        $lk = $this->pdScopeConfig->getValue(Constants::OAUTH_LK);
        return !empty($lk);
    }

    public function getLicensePlan(): string
    {
        $plan = $this->pdScopeConfig->getValue(Constants::LICENSE_PLAN);
        return $plan ? (string) $plan : Constants::PLAN_TRIAL;
    }

    public function getCustomerEmail(): string
    {
        $email = $this->pdScopeConfig->getValue(Constants::CUSTOMER_EMAIL);
        return $email ? (string) $email : '';
    }

    public function setCustomerEmail(string $email): void
    {
        $this->saveConfig(Constants::CUSTOMER_EMAIL, $email);
    }

    public function setLicensePlan(string $plan): void
    {
        $this->saveConfig(Constants::LICENSE_PLAN, $plan);
    }

    public function getOAuthLk(): string
    {
        $v = $this->pdScopeConfig->getValue(Constants::OAUTH_LK);
        if (!$v) {
            return '';
        }
        return Encryption::decrypt((string) $v, Constants::DEFAULT_TOKEN_VALUE);
    }

    public function setOAuthLk(string $lk): void
    {
        $this->saveConfig(Constants::OAUTH_LK, $lk !== '' ? Encryption::encrypt($lk, Constants::DEFAULT_TOKEN_VALUE) : '');
    }

    public function setOAuthCkl(string $ckl): void
    {
        $this->saveConfig(Constants::OAUTH_CKL, $ckl !== '' ? Encryption::encrypt($ckl, Constants::DEFAULT_TOKEN_VALUE) : '');
    }

    public function clearLicenseData(): void
    {
        $this->saveConfig(Constants::OAUTH_LK, '');
        $this->saveConfig(Constants::OAUTH_CKL, '');
        $this->saveConfig(Constants::LICENSE_PLAN, '');
        $this->saveConfig(Constants::LICENSE_SYNC_DATE, '');
        $this->saveConfig(Constants::LICENSE_EXPIRY_DATE, '');
    }

    /** Date/time at which the license key was last validated. Mirrors SSO LICENSE_TIME_STAMP. */
    public function getLicenseSyncDate(): ?string
    {
        $v = $this->pdScopeConfig->getValue(Constants::LICENSE_SYNC_DATE);
        return $v ? (string) $v : null;
    }

    public function setLicenseSyncDate(string $date): void
    {
        $this->saveConfig(Constants::LICENSE_SYNC_DATE, $date);
    }

    /** License expiry date returned by the vml API (`licenseExpiry` field). Mirrors SSO LICENSE_EXPIRY_DATE. */
    public function getLicenseExpiryDate(): ?string
    {
        $v = $this->pdScopeConfig->getValue(Constants::LICENSE_EXPIRY_DATE);
        return $v ? (string) $v : null;
    }

    public function setLicenseExpiryDate(string $date): void
    {
        $this->saveConfig(Constants::LICENSE_EXPIRY_DATE, $date);
    }

    public function getOAuthCkl(): string
    {
        $v = $this->pdScopeConfig->getValue(Constants::OAUTH_CKL);
        if (!$v) {
            return '';
        }
        $decrypted = Encryption::decrypt((string) $v, Constants::DEFAULT_TOKEN_VALUE);
        return is_numeric(trim($decrypted)) ? $decrypted : (string) $v;
    }

    /** Customer's own API key — returned by the login API as 'apiKey'. Mirrors SSO API_KEY. */
    public function getOAuthApiKey(): string
    {
        $v = $this->pdScopeConfig->getValue(Constants::OAUTH_API_KEY);
        return $v ? (string) $v : '';
    }

    public function setOAuthApiKey(string $key): void
    {
        $this->saveConfig(Constants::OAUTH_API_KEY, $key);
    }

    // ── Internal ──────────────────────────────────────────────

    /**
     * Thin wrapper kept for internal call-site consistency.
     * Delegates to the inherited setStoreConfig() which handles write + flush + reinit.
     */
    private function saveConfig(string $path, string $value): void
    {
        $this->setStoreConfig($path, $value);
    }
}
