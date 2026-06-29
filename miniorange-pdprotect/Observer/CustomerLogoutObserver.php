<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CustomerLogoutObserver implements ObserverInterface
{
    private const COOKIE_NAME = 'mopdp_consent';

    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
    ) {}

    public function execute(Observer $observer): void
    {
        try {
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setPath('/');
            $this->cookieManager->deleteCookie(self::COOKIE_NAME, $metadata);
        } catch (\Throwable) {
            // Non-fatal — if deletion fails the next user's login observer
            // will clear the cookie when their consent check runs.
        }
    }
}
