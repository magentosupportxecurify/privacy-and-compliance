<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use MiniOrange\PDProtect\Model\ConsentLogger;

class CustomerLoginObserver implements ObserverInterface
{
    private const COOKIE_NAME        = 'mopdp_consent';
    private const COOKIE_DURATION    = 365 * 24 * 60 * 60; // 1 year in seconds
    private const COUNTRY_SESSION_KEY = 'mopdp_visitor_country';

    public function __construct(
        private readonly ConsentLogger $consentLogger,
        private readonly SessionManagerInterface $session,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer   = $observer->getEvent()->getCustomer();
        $customerId = (int) $customer->getId();
        $email      = (string) $customer->getEmail();

        if ($this->consentLogger->hasActiveConsent($customerId)) {
            // Customer already gave consent (DB is source of truth).
            // Restore the cookie so the JS popup does not show on this device.
            $this->setCookie();
            return;
        }

        // No DB record for this customer.
        if ($this->cookieManager->getCookie(self::COOKIE_NAME) !== null) {
            // Cookie exists — the customer gave consent as a guest on this same
            // device before logging in. Link the guest consent to their account.
            $countryCode = (string) $this->session->getData(self::COUNTRY_SESSION_KEY);
            $this->consentLogger->log('accepted', $customerId, $countryCode, $email);
            // Cookie already set — no further action needed.
            return;
        }

        // No DB record and no cookie → popup will show naturally on next page load.
    }

    private function setCookie(): void
    {
        try {
            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(self::COOKIE_DURATION)
                ->setPath('/')
                ->setSameSite('Lax');
            $this->cookieManager->setPublicCookie(self::COOKIE_NAME, 'accepted', $metadata);
        } catch (\Throwable) {
            // Non-fatal — if cookie cannot be set the popup will show;
            // the DB record prevents double-logging.
        }
    }
}
