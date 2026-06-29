<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use MiniOrange\PDProtect\Model\ConsentLogger;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

class WithdrawConsent implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly ConsentLogger $consentLogger,
        private readonly ResourceConnection $resource,
        private readonly PremiumHelper $premiumHelper,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory
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
            return $result->setData(['success' => false, 'message' => __('Please log in to continue.')->render()]);
        }

        $customerId = (int) $this->customerSession->getCustomerId();

        try {
            // Remove prior consent records first so the withdrawal entry persists
            $this->resource->getConnection()->delete(
                $this->resource->getTableName('miniorange_pdprotect_consent_log'),
                ['customer_id = ?' => $customerId]
            );

            // Log withdrawal after the delete so this entry is not erased
            $email = $this->customerSession->getCustomer()->getEmail();
            $this->consentLogger->log('withdrawn', $customerId, '', $email ?: null);

            // Delete the consent cookie server-side so the browser's cookie jar is
            // cleared before the client-side redirect fires. This prevents a race
            // where the browser queues the privacy-page GET request before JS-side
            // cookie deletion is processed, causing PHP to still see the cookie and
            // suppress the popup on the first page after withdrawal.
            try {
                $metadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setPath('/');
                $this->cookieManager->deleteCookie('mopdp_consent', $metadata);
            } catch (\Throwable) {
                // Non-fatal: confirm.js client-side deletion still handles it.
            }

            $this->premiumHelper->flushCache('WithdrawConsent');

            return $result->setData([
                'success' => true,
                'message' => 'Your consent has been withdrawn and your consent records have been deleted.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => 'Could not withdraw consent: ' . $e->getMessage()]);
        }
    }
}
