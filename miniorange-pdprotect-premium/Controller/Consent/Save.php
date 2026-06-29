<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Consent;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtect\Model\ConsentLogger;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override of PDProtect\Controller\Consent\Save.
 *
 * Adds a module-active gate: if the trial has expired and no license is
 * activated, all consent-save requests are rejected server-side so that
 * removing the `disabled` attribute via DevTools has no effect.
 */
class Save implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const COUNTRY_SESSION_KEY = 'mopdp_visitor_country';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CustomerSession $customerSession,
        private readonly SessionManagerInterface $session,
        private readonly ConsentLogger $consentLogger,
        private readonly Data $dataHelper,
        private readonly PremiumHelper $premiumHelper
    ) {}

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->premiumHelper->isModuleActive()) {
            $this->dataHelper->log_debug('Consent\Save: module not active, rejecting');
            return $result->setData(['success' => false, 'message' => 'Module not active.']);
        }

        $status = $this->request->getParam('consent', '');
        $this->dataHelper->log_debug("Consent\\Save: execute, consent={$status}");

        if (!in_array($status, ['accepted'], true)) {
            $this->dataHelper->log_debug("Consent\\Save: invalid value '{$status}'");
            return $result->setData(['success' => false, 'message' => 'Invalid consent value']);
        }

        $logConsent = (bool) $this->scopeConfig->getValue(
            'pdprotect/general/log_guest_consent',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $isLoggedIn = $this->customerSession->isLoggedIn();
        $this->dataHelper->log_debug(
            'Consent\Save: log_consent=' . ($logConsent ? 'true' : 'false')
            . ', isLoggedIn=' . ($isLoggedIn ? 'true' : 'false')
        );

        $countryCode = (string) $this->session->getData(self::COUNTRY_SESSION_KEY);

        if (!$logConsent) {
            $this->dataHelper->log_debug('Consent\Save: skipping DB log (Record consent disabled)');
        } elseif ($isLoggedIn) {
            $this->dataHelper->log_debug('Consent\Save: writing consent log to DB (logged-in customer)');
            $customer = $this->customerSession->getCustomer();
            $this->consentLogger->log($status, (int) $customer->getId(), $countryCode, $customer->getEmail());
        } else {
            $this->dataHelper->log_debug('Consent\Save: writing consent log to DB (guest)');
            $this->consentLogger->log($status, null, $countryCode, null);
        }

        return $result->setData(['success' => true]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
