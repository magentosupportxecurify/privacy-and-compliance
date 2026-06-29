<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Customer;

use Magento\Customer\Model\Authentication as CustomerAuthentication;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;
use MiniOrange\PDProtect\Model\PersonalDataAnonymizer;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

class AnonymizeData implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly PersonalDataAnonymizer $anonymizer,
        private readonly PremiumHelper $premiumHelper,
        private readonly RequestInterface $request,
        private readonly CustomerAuthentication $customerAuthentication
    ) {}

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->premiumHelper->isModuleActive()) {
            return $result->setData(['success' => false, 'message' => 'This feature is not available. Please activate your license.']);
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => 'Not logged in.']);
        }

        $password = (string) $this->request->getParam('password', '');
        if ($password === '') {
            return $result->setData(['success' => false, 'message' => __('Please enter your password to confirm.')->render()]);
        }
        try {
            $this->customerAuthentication->authenticate(
                (int) $this->customerSession->getCustomerId(),
                $password
            );
        } catch (UserLockedException $e) {
            return $result->setData(['success' => false, 'message' => __('Your account is temporarily locked. Please try again later.')->render()]);
        } catch (InvalidEmailOrPasswordException $e) {
            return $result->setData(['success' => false, 'message' => __('Incorrect password. Please try again.')->render()]);
        }

        $customerId = (int) $this->customerSession->getCustomerId();

        try {
            if ($this->anonymizer->isCustomerAnonymized($customerId)) {
                return $result->setData(['success' => false, 'message' => 'Your data has already been anonymized.']);
            }

            $this->anonymizer->anonymizeCustomer($customerId);
            $this->premiumHelper->flushCache('AnonymizeData');

            return $result->setData([
                'success' => true,
                'message' => 'Your personal data has been anonymized successfully.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => 'Could not anonymize data: ' . $e->getMessage()]);
        }
    }
}
