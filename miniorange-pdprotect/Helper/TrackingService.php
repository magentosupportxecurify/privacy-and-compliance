<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Helper;

use Magento\Backend\Model\Auth\Session as BackendSession;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;

class TrackingService
{
    private const ENDPOINT = 'https://magento.miniorange.com/plugin-portal/api/tracking';
    private const MODULE   = 'MiniOrange_PDProtect';

    public function __construct(
        private readonly Curl $curl,
        private readonly WriterInterface $configWriter,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly ModuleListInterface $moduleList,
        private readonly StoreManagerInterface $storeManager,
        private readonly BackendSession $backendSession,
        private readonly TypeListInterface $cacheTypeList,
        private readonly ReinitableConfigInterface $reinitableConfig
    ) {}

    public function trackInstallation(string $page = ''): void
    {
        try {
            $dateTime  = new \DateTime();
            $now       = $dateTime->format('Y-m-d H:i:s');
            $timestamp = $dateTime->getTimestamp();

            $payload = $this->buildPayload('install');
            $payload['timeStamp']            = $timestamp;
            $payload['IsFreeInstalled']      = 'Yes';
            $payload['FreeInstalledDate']    = $now;
            $payload['pluginFirstPageVisit'] = $page;

            $this->post($payload);

            // Store Unix timestamp as guard — mirrors TwoFA's TIMESTAMP constant
            $this->saveConfigGuard('pdprotect/tracking/timestamp', (string) $timestamp);
        } catch (\Throwable $e) {
            // Tracking is best-effort; never break admin on failure
        }
    }

    public function buildPayload(string $event): array
    {
        $adminUser  = $this->backendSession->getUser();
        $adminEmail = $adminUser ? (string) $adminUser->getEmail() : '';

        try {
            $domain = (string) $this->storeManager->getStore()->getBaseUrl();
        } catch (\Throwable $e) {
            $domain = '';
        }

        $module        = $this->moduleList->getOne(self::MODULE);
        $pluginVersion = $module ? (string) ($module['setup_version'] ?? '1.0.0') : '1.0.0';

        if($event == 'trial_expired')
        {
            return [
                'pluginName'         => 'miniOrange Personal Data Protection',
                'pluginVersion'      => $pluginVersion,
                'domain'             => $domain,
                'adminEmail'         => $adminEmail,
                'environmentName'    => $this->productMetadata->getEdition(),
                'environmentVersion' => $this->productMetadata->getVersion(),
                'IsTrialExpired'     => 'Yes',
            ];
        }
        else if($event == 'trial_extended')
        {
            return [
                'pluginName'         => 'miniOrange Personal Data Protection',
                'pluginVersion'      => $pluginVersion,
                'domain'             => $domain,
                'adminEmail'         => $adminEmail,
                'environmentName'    => $this->productMetadata->getEdition(),
                'environmentVersion' => $this->productMetadata->getVersion(),
                'IsTrialExtended'     => 'Yes',
            ];
        }

        return [
            'pluginName'         => 'miniOrange Personal Data Protection',
            'pluginVersion'      => $pluginVersion,
            'domain'             => $domain,
            'adminEmail'         => $adminEmail,
            'environmentName'    => $this->productMetadata->getEdition(),
            'environmentVersion' => $this->productMetadata->getVersion(),
            'event'              => $event,
        ];
    }

    /**
     * Write a tracking guard to config, then flush the config cache and reinit so the
     * guard is visible to ScopeConfig on subsequent requests without a manual cache flush.
     */
    protected function saveConfigGuard(string $path, string $value): void
    {
        $this->configWriter->save($path, $value, 'default', 0);
        $this->cacheTypeList->cleanType('config');
        $this->reinitableConfig->reinit();
    }

    protected function post(array $data): void
    {
        $this->curl->setTimeout(5);
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->curl->post(self::ENDPOINT, json_encode($data) ?: '{}');
    }
}
