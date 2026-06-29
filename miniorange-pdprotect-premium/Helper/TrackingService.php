<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Helper;

use Magento\Backend\Model\Auth\Session as BackendSession;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;

class TrackingService extends \MiniOrange\PDProtect\Helper\TrackingService
{
    private const MODULE = 'MiniOrange_PDProtectPremium';

    /** Local reference to WriterInterface — parent uses private readonly, inaccessible in child */
    private WriterInterface $premiumConfigWriter;
    private ScopeConfigInterface $premiumScopeConfig;

    public function __construct(
        Curl $curl,
        WriterInterface $configWriter,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        StoreManagerInterface $storeManager,
        BackendSession $backendSession,
        TypeListInterface $cacheTypeList,
        ReinitableConfigInterface $reinitableConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->premiumConfigWriter = $configWriter;
        $this->premiumScopeConfig  = $scopeConfig;
        parent::__construct($curl, $configWriter, $productMetadata, $moduleList, $storeManager, $backendSession, $cacheTypeList, $reinitableConfig);
    }

    /**
     * Adds the stored install timestamp so all premium events reference the same
     * backend record as the initial trial_install entry.
     * Mirrors TwoFA: every sendUserDetailsToPortal() call passes the same stored timeStamp.
     * trackTrialInstall() overwrites timeStamp with the precise install value after calling this.
     */
    public function buildPayload(string $event): array
    {
        $payload = parent::buildPayload($event);
        $stored  = (int) ($this->premiumScopeConfig->getValue(Constants::TRACKING_TIMESTAMP) ?? 0);
        $payload['timeStamp'] = $stored ?: time();
        return $payload;
    }

    /**
     * Track a named premium lifecycle event (trial_install, trial_expired, trial_extended, plan_activation).
     */
    public function trackEvent(string $event, array $extra = []): void
    {
        try {
            $payload = array_merge($this->buildPayload($event), $extra);
            $this->post($payload);
        } catch (\Throwable $e) {
            // Best-effort
        }
    }

    /**
     * Track trial installation — mirrors TwoFA premium isFirstPageVisit() IsTrialInstalled=Yes pattern.
     * Sets TRIAL_PLAN_CONSTANT guard (= '1') so this fires only once.
     *
     * When free module was installed before premium ($freeTimestamp is non-empty), uses the free
     * module's install timestamp as TrialInstalledDate so both installs share a single consistent
     * date in the tracking backend record.
     *
     * @param string $page          Current admin page path (mirrors TwoFA's $page parameter)
     * @param string $freeTimestamp Unix timestamp string from pdprotect/tracking/timestamp, or ''
     */
    public function trackTrialInstall(string $page = '', string $freeTimestamp = ''): void
    {
        try {
            if ($freeTimestamp !== '') {
                // Free module was installed first — reuse its timestamp for a single consolidated entry
                $dt   = (new \DateTime())->setTimestamp((int) $freeTimestamp);
                $date = $dt->format('Y-m-d H:i:s');
                $ts   = (int) $freeTimestamp;
            } else {
                $dt   = new \DateTime();
                $date = $dt->format('Y-m-d H:i:s');
                $ts   = $dt->getTimestamp();
            }

            $payload = $this->buildPayload('trial_install');
            $payload['timeStamp']            = $ts;
            $payload['IsTrialInstalled']     = 'Yes';
            $payload['TrialInstalledDate']   = $date;
            $payload['pluginFirstPageVisit'] = $page;

            if ($freeTimestamp !== '') {
                // Include free-install fields so the tracking backend gets a single merged record
                $payload['IsFreeInstalled']   = 'Yes';
                $payload['FreeInstalledDate'] = $date; // same date — single timestamp
            }

            $this->post($payload);

            // Mirrors TwoFA: set TRIAL_PLAN_CONSTANT to '1' as the guard
            $this->saveConfigGuard(Constants::TRACKING_TRIAL_PLAN_CONSTANT, '1');

            // For premium-only installs (no free module), persist $ts so subsequent events
            // (trial_expired, trial_extended, plan_activation) can reuse the same timestamp
            // and the backend links them to this installation record.
            if ($freeTimestamp === '') {
                $this->saveConfigGuard(Constants::TRACKING_TIMESTAMP, (string) $ts);
            }
        } catch (\Throwable $e) {
            // Best-effort
        }
    }

    /**
     * Track trial expiry — PDProtect-specific guard (TwoFA does not track expiry).
     * Sets trial_expired_sent = '1' so this fires only once.
     */
    public function trackTrialExpired(): void
    {
        try {
            $this->trackEvent('trial_expired');
            $this->saveConfigGuard(Constants::TRACKING_TRIAL_EXPIRED_SENT, '1');
        } catch (\Throwable $e) {
            // Best-effort
        }
    }

    /**
     * Track trial extension — fires only once (guarded by TRACKING_TRIAL_EXTENDED_SENT).
     * Called from ExtendTrialAction after a successful extension.
     */
    public function trackTrialExtended(): void
    {
        try {
            $this->trackEvent('trial_extended');
            $this->saveConfigGuard(Constants::TRACKING_TRIAL_EXTENDED_SENT, '1');
        } catch (\Throwable $e) {
            // Best-effort
        }
    }

    public function trackPlanActivation(string $plan): void
    {
        $this->trackEvent('plan_activation', [
            'pluginPlan'          => $plan,
            'IsPremiumInstalled'   => 'Yes',
            'PremiumInstalledDate' => date('Y-m-d H:i:s'),
        ]);
        $this->saveConfigGuard(Constants::TRACKING_PLAN_ACTIVATION_SENT, '1');
    }
}
