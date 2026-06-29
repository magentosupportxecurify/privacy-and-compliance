<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Frontend\Consent;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Session\SessionManagerInterface;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtect\Model\ConsentLogger;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override of PDProtect\Controller\Frontend\Consent\Save.
 *
 * Adds a module-active gate: if the trial has expired and no license is
 * activated, all consent-save requests are rejected server-side.
 */
class Save implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const COUNTRY_SESSION_KEY = 'mopdp_visitor_country';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly FormKeyValidator $formKeyValidator,
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
            return $result->setData(['success' => false, 'message' => 'Module not active.']);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => 'Invalid form key']);
        }

        $status = $this->request->getParam('consent', '');

        if (!in_array($status, ['accepted'], true)) {
            return $result->setData(['success' => false, 'message' => 'Invalid consent value']);
        }

        $logGuests  = (bool) $this->scopeConfig->getValue(
            'pdprotect/general/log_guest_consent',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $isLoggedIn = $this->customerSession->isLoggedIn();

        if ($logGuests && !$isLoggedIn) {
            $countryCode = (string) $this->session->getData(self::COUNTRY_SESSION_KEY);
            $this->consentLogger->log($status, null, $countryCode);
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
