<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use MiniOrange\CookieConsent\Helper\Data as PrivacyHelper;

class Banner extends Template
{
    public const CONSENT_STORAGE_KEY = 'mo_consent_preferences';

    public function __construct(
        Context $context,
        private readonly PrivacyHelper $privacyHelper,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCacheKeyInfo(): array
    {
        return [
            'MO_COOKIE_BANNER',
            (string) $this->storeManager->getStore()->getId(),
        ];
    }

    public function isEnabled(): bool
    {
        return $this->privacyHelper->isBannerEnabled((int) $this->storeManager->getStore()->getId());
    }

    public function getBannerConfig(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return [
            'saveUrl' => $this->getUrl('miniorange/consent/save'),
            'position' => $this->privacyHelper->getPosition($storeId),
            'storageKey' => self::CONSENT_STORAGE_KEY,
        ];
    }

    public function getBannerConfigJson(): string
    {
        return (string) json_encode($this->getBannerConfig());
    }

    public function getConsentStorageKey(): string
    {
        return self::CONSENT_STORAGE_KEY;
    }

    public function getBannerTitle(): string
    {
        return $this->privacyHelper->getBannerTitle((int) $this->storeManager->getStore()->getId());
    }

    public function getBannerBody(): string
    {
        return $this->privacyHelper->getBannerBody((int) $this->storeManager->getStore()->getId());
    }

    public function getPrivacyUrl(): string
    {
        return $this->privacyHelper->getPrivacyUrl((int) $this->storeManager->getStore()->getId());
    }
}
