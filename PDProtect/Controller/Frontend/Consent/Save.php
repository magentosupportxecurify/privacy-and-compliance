<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Frontend\Consent;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\ScopeInterface;
use MiniOrange\PDProtect\Helper\Data;
use MiniOrange\PDProtect\Model\ConsentLogger;

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
        private readonly Data $dataHelper
    ) {}

    public function execute()
    {
        $this->dataHelper->log_debug('Frontend\Consent\Save: execute');
        $result = $this->jsonFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->dataHelper->log_debug('Frontend\Consent\Save: invalid form key, rejecting');
            return $result->setData(['success' => false, 'message' => 'Invalid form key']);
        }

        $status = $this->request->getParam('consent', '');
        $this->dataHelper->log_debug("Frontend\\Consent\\Save: received consent={$status}");

        if (!in_array($status, ['accepted'], true)) {
            $this->dataHelper->log_debug("Frontend\\Consent\\Save: invalid consent value '{$status}'");
            return $result->setData(['success' => false, 'message' => 'Invalid consent value']);
        }

        $logGuests  = (bool) $this->scopeConfig->getValue(
            'pdprotect/general/log_guest_consent',
            ScopeInterface::SCOPE_STORE
        );
        $isLoggedIn = $this->customerSession->isLoggedIn();

        $this->dataHelper->log_debug(
            'Frontend\Consent\Save: log_guest_consent=' . ($logGuests ? 'true' : 'false')
            . ', isLoggedIn=' . ($isLoggedIn ? 'true' : 'false')
        );

        if ($logGuests && !$isLoggedIn) {
            $this->dataHelper->log_debug('Frontend\Consent\Save: writing consent log to DB');
            $countryCode = (string) $this->session->getData(self::COUNTRY_SESSION_KEY);
            $this->consentLogger->log($status, null, $countryCode);
        } else {
            $this->dataHelper->log_debug('Frontend\Consent\Save: skipping DB log');
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
