<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Helper;

use Magento\Backend\Model\Auth\Session as AdminAuthSession;
use Magento\Framework\App\Cache\Type\Block as BlockCacheType;
use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\PageCache\Model\Cache\Type as FullPageCacheType;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;

/**
 * Store config read/write for MO Privacy (same pattern as MiniOrange TwoFA helper).
 */
class Data extends AbstractHelper
{
    private const CONFIG_PATH_PREFIX = 'miniorange/CookieConsent/';
    protected $readFactory;
    protected $componentRegistrar;

    public function __construct(
        Context $context,
        public WriterInterface $configWriter,
        public TypeListInterface $typeList,
        public AdminAuthSession $authSession,
        public StoreManagerInterface $storeManager,
        public ProductMetadataInterface $productMetadata,
        public ModuleListInterface $moduleList,
        public SupportCurl $supportCurl,
        ReadFactory $readFactory,
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->supportCurl = $supportCurl;
        $this->authSession = $authSession;
        $this->configWriter = $configWriter;
        $this->typeList = $typeList;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * Read value from core_config_data.
     *
     * @param string $config Short path after miniorange/CookieConsent/ (e.g. general/enabled).
     * @param string $scope
     * @param int|string|null $scopeId
     * @return string|int|null
     */
    private ?int $sandboxStoreId = null;
    private bool $sandboxScopeResolved = false;

    public function getEffectiveSandboxStoreId(): ?int
    {
        return $this->resolveSandboxStoreId();
    }

    private function resolveSandboxStoreId(): ?int
    {
        if (!$this->sandboxScopeResolved) {
            $transport = new \Magento\Framework\DataObject(['store_id' => null]);
            $this->_eventManager->dispatch('mo_cookieconsent_get_scope', ['transport' => $transport]);
            $id = $transport->getData('store_id');
            $this->sandboxStoreId = $id !== null ? (int) $id : null;
            $this->sandboxScopeResolved = true;
        }
        return $this->sandboxStoreId;
    }

    public function getStoreConfig($config, $scope = 'default', $scopeId = null)
    {
        $fullPath = self::CONFIG_PATH_PREFIX . $config;
        $storeId  = $this->resolveSandboxStoreId();
        if ($storeId !== null) {
            return $this->scopeConfig->getValue($fullPath, ScopeInterface::SCOPE_STORE, $storeId);
        }
        if ($scope === 'default' || $scopeId === null) {
            return $this->scopeConfig->getValue($fullPath);
        }
        return $this->scopeConfig->getValue($fullPath, $scope, $scopeId);
    }

    public function setStoreConfig($config, $value, $scope = 'default', $scopeId = null): void
    {
        $fullPath  = self::CONFIG_PATH_PREFIX . $config;
        $transport = new \Magento\Framework\DataObject([
            'path'    => $fullPath,
            'value'   => $value,
            'handled' => false,
        ]);
        $this->_eventManager->dispatch('mo_cookieconsent_config_save', ['transport' => $transport]);

        if (!$transport->getData('handled')) {
            if ($scope === 'default' || $scopeId === null) {
                $this->configWriter->save($fullPath, $value);
            } else {
                $this->configWriter->save($fullPath, $value, $scope, $scopeId);
            }
        }
        $this->flushCache('setStoreConfig:' . $config);
    }

    public function isBannerEnabled(?int $storeId = null): bool
    {
        if ($storeId !== null) {
            return (bool) $this->getStoreConfig('general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
        }

        return (bool) $this->getStoreConfig('general/enabled');
    }

    public function getPosition(?int $storeId = null): string
    {
        $value = $storeId !== null
            ? $this->getStoreConfig('general/position', ScopeInterface::SCOPE_STORE, $storeId)
            : $this->getStoreConfig('general/position');

        return $value !== null && $value !== '' ? (string) $value : 'bottom';
    }

    public function getBannerTitle(?int $storeId = null): string
    {
        $value = $storeId !== null
            ? $this->getStoreConfig('general/banner_title', ScopeInterface::SCOPE_STORE, $storeId)
            : $this->getStoreConfig('general/banner_title');

        return $value !== null && $value !== '' ? (string) $value : (string) __('We use cookies');
    }

    public function getBannerBody(?int $storeId = null): string
    {
        $value = $storeId !== null
            ? $this->getStoreConfig('general/banner_body', ScopeInterface::SCOPE_STORE, $storeId)
            : $this->getStoreConfig('general/banner_body');

        return $value !== null ? (string) $value : '';
    }

    public function getPrivacyUrl(?int $storeId = null): string
    {
        $value = $storeId !== null
            ? $this->getStoreConfig('general/privacy_url', ScopeInterface::SCOPE_STORE, $storeId)
            : $this->getStoreConfig('general/privacy_url');

        return $value !== null && $value !== '' ? (string) $value : '/privacy-policy';
    }

    public function isScriptBlockerEnabled(?int $storeId = null): bool
    {
        if ($storeId !== null) {
            return (bool) $this->getStoreConfig('script_manager/enabled', ScopeInterface::SCOPE_STORE, $storeId);
        }

        return (bool) $this->getStoreConfig('script_manager/enabled');
    }

    /**
     * Script Blocker runs on the storefront only when both the blocker flag and cookie banner are enabled.
     */
    public function isScriptBlockerActiveForFrontend(?int $storeId = null): bool
    {
        return $this->isScriptBlockerEnabled($storeId) && $this->isBannerEnabled($storeId);
    }

    /**
     * @return array<string, string> lowercase URL (or pattern) => category
     */
    public function getDiscoveredMap(?int $storeId = null): array
    {
        $value = $storeId !== null
            ? $this->getStoreConfig('script_manager/discovered_map', ScopeInterface::SCOPE_STORE, $storeId)
            : $this->getStoreConfig('script_manager/discovered_map');

        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getCustomPatternsRaw(?int $storeId = null): string
    {
        $value = $storeId !== null
            ? $this->getStoreConfig('script_manager/custom_patterns', ScopeInterface::SCOPE_STORE, $storeId)
            : $this->getStoreConfig('script_manager/custom_patterns');

        return $value !== null ? (string) $value : '';
    }

    /**
     * Invalidate config, block HTML, and full-page cache types so storefront reflects saved settings immediately.
     *
     * @param string $from Optional context for logs (e.g. caller identifier).
     */
    public function flushCache(string $from = ''): void
    {
        $types = [
            ConfigCacheType::TYPE_IDENTIFIER,
            BlockCacheType::TYPE_IDENTIFIER,
            FullPageCacheType::TYPE_IDENTIFIER,
        ];
        $context = $from !== '' ? ' Source: ' . $from : '';
        foreach ($types as $type) {
            try {
                $this->typeList->cleanType($type);
                $this->log_debug('FlushCache: ' . $type . ' cache cleaned.' . $context);
            } catch (\Throwable $e) {
                $this->log_debug('FlushCache: ' . $type . ' cache clean failed: ' . $e->getMessage(), $e);
            }
        }
    }

    public function log_debug($msg = "", $obj = null)
    {
        if (is_object($msg)) {
            $this->_logger->debug("MO Privacy Plugin : " . print_r($obj, true));
        } else {
            $this->_logger->debug("MO Privacy Plugin : " . $msg);
        }

        if ($obj != null) {
            $this->_logger->debug("MO Privacy Plugin : " . var_export($obj, true));
        }
    }

    public function writePluginInstallationData($activeTab){
        $email = $this->getCurrentAdminUser()->getEmail();
        $domain = $this->getBaseUrl();
        $environmentType = $this->getEdition();
        $timestamp = $this->getStoreConfig('privacy_timestamp') ?? time();
        $this->setStoreConfig('privacy_timestamp', $timestamp);
        $this->flushCache('writePluginInstallationData');
        $data = [
            'timeStamp' => $timestamp,
            'adminEmail' => $email,
            'domain' => $domain,
            'pluginName' => 'miniOrange Cookie Consent',
            'pluginVersion' => $this->getCurrentModuleVersion(),
            'environmentName' => $environmentType,
            'environmentVersion' => $this->getProductVersion(),
            'pluginFirstPageVisit' => $activeTab,
            'IsFreeInstalled' => 'Yes',
            'FreeInstalledDate' => date('Y-m-d H:i:s'),
            'pluginPlan' => SupportConstants::FREE_PLAN_TELEMETRY
        ];
        SupportCurl::sync_plugin_metrics($data);
    }

    /**
     * Get the Current Admin User who is logged in
     */
    public function getCurrentAdminUser()
    {
        return $this->authSession->getUser();
    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get the edition.
     */
    public function getEdition()
    {
        return $this->productMetadata->getEdition() == 'Community' ? 'Magento Open Source':'Adobe Commerce Enterprise/Cloud';
    }

    public function getProductVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    public function getCurrentModuleVersion()
    {
        $path = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            'MiniOrange_CookieConsent'
        );
        $directoryRead = $this->readFactory->create($path);
        $composerJsonData = $directoryRead->readFile('composer.json');
        $data = json_decode($composerJsonData, true);
        
        return $data['version'] ?? '1.0.0';
    }
}
