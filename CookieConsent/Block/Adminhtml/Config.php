<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use MiniOrange\CookieConsent\Helper\Data as PrivacyHelper;

class Config extends Template
{
    private const TAB_CHILD_NAMES = [
        'banner_settings' => 'mo_privacy_tab_banner_settings',
        'script_manager' => 'mo_privacy_tab_script_manager',
        'upgrade' => 'mo_privacy_tab_upgrade',
    ];

    public function __construct(
        Context $context,
        private readonly PrivacyHelper $privacyHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getActiveTab(): string
    {
        $tab = (string) $this->getRequest()->getParam('active_tab', 'banner_settings');

        return array_key_exists($tab, self::TAB_CHILD_NAMES) ? $tab : 'banner_settings';
    }

    public function getTabChildName(): string
    {
        return self::TAB_CHILD_NAMES[$this->getActiveTab()];
    }

    /**
     * @param string $shortKey Path after miniorange/CookieConsent/
     * @param string|int|null $default
     * @return string|int|null
     */
    public function getConfigValue(string $shortKey, $default = '')
    {
        $value = $this->privacyHelper->getStoreConfig($shortKey);

        return $value !== null && $value !== '' ? $value : $default;
    }

    public function isEnabled(string $shortKey): bool
    {
        return (bool) $this->privacyHelper->getStoreConfig($shortKey);
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('mocookieconsent/index/save');
    }

    public function getScanRunUrl(): string
    {
        return $this->getUrl('mocookieconsent/scan/run');
    }

    public function isCookieBannerEnabled(): bool
    {
        return (bool) $this->privacyHelper->getStoreConfig('general/enabled');
    }

    /**
     * @return list<array{url: string, category: string}>
     */
    public function getDiscoveredScriptRows(): array
    {
        $map = $this->privacyHelper->getDiscoveredMap();
        $rows = [];
        foreach ($map as $url => $category) {
            $rows[] = ['url' => (string) $url, 'category' => (string) $category];
        }
        usort($rows, static fn (array $a, array $b): int => strcmp($a['url'], $b['url']));

        return $rows;
    }

    public function getFormKeyHtml(): string
    {
        return $this->getLayout()->createBlock(\Magento\Framework\View\Element\FormKey::class)->toHtml();
    }

        /**
     * Write plugin installation data to the database
     */
    public function writePluginInstallation($activeTab)
    {
        $this->privacyHelper->log_debug('writePluginInstallation: ' . $activeTab);
        $pluginInstallationWritten = $this->privacyHelper->getStoreConfig('privacy_timestamp');
        $this->privacyHelper->log_debug('writePluginInstallation: ' . $pluginInstallationWritten);
        if (!$pluginInstallationWritten) {
            $this->privacyHelper->writePluginInstallationData($activeTab);
        }
        $this->privacyHelper->flushCache();
    }

    public function getCurrentModuleVersion()
    {
        return $this->privacyHelper->getCurrentModuleVersion();
    }
}
