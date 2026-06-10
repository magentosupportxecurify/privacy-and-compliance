<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Observer;

use Magento\Framework\App\Area;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use MiniOrange\CookieConsent\Helper\Data as PrivacyHelper;
use MiniOrange\CookieConsent\Model\ScriptBlocker;

class TransformPageResponse implements ObserverInterface
{
    public function __construct(
        private readonly PrivacyHelper $privacyHelper,
        private readonly ScriptBlocker $scriptBlocker,
        private readonly State $appState,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            if ($this->appState->getAreaCode() !== Area::AREA_FRONTEND) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        /** @var HttpResponse $response */
        $response = $observer->getData('response');
        if (!$response instanceof HttpResponse) {
            return;
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->privacyHelper->isScriptBlockerActiveForFrontend($storeId)) {
            return;
        }

        $body = (string) $response->getBody();
        if ($body !== '' && stripos($body, '<html') !== false) {
            $response->setBody($this->scriptBlocker->processHtml($body, $storeId));
        }
    }
}
