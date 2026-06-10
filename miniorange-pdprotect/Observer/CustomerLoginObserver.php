<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use MiniOrange\PDProtect\Model\ConsentLogger;

/**
 * When a customer logs in during a session where consent was already granted
 * as a guest (mopdp_consent cookie is present), log a new consent entry that
 * associates the consent with the now-known customer ID and email address.
 */
class CustomerLoginObserver implements ObserverInterface
{
    private const COUNTRY_SESSION_KEY = 'mopdp_visitor_country';

    public function __construct(
        private readonly ConsentLogger $consentLogger,
        private readonly SessionManagerInterface $session,
        private readonly CookieManagerInterface $cookieManager
    ) {}

    public function execute(Observer $observer): void
    {
        // Only log if a consent cookie already exists for this browser session
        if ($this->cookieManager->getCookie('mopdp_consent') === null) {
            return;
        }

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer    = $observer->getEvent()->getCustomer();
        $customerId  = (int) $customer->getId();
        $email       = (string) $customer->getEmail();
        $countryCode = (string) $this->session->getData(self::COUNTRY_SESSION_KEY);

        $this->consentLogger->log('accepted', $customerId, $countryCode, $email);
    }
}
