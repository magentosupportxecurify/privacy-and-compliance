<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Customer;

use Magento\Customer\Model\Authentication as CustomerAuthentication;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

/**
 * Premium override: inserts a pending deletion request into the database.
 * The free stub returns an error; this implementation actually creates the request.
 */
class RequestDeletion implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly ResourceConnection $resource,
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
            return $result->setData(['success' => false, 'message' => 'Please log in to continue.']);
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

        try {
            $customer   = $this->customerSession->getCustomer();
            $connection = $this->resource->getConnection();
            $table      = $this->resource->getTableName('miniorange_pdprotect_deletion_request');

            // Prevent duplicate pending requests for the same customer
            $existing = $connection->fetchOne(
                "SELECT request_id FROM {$table} WHERE customer_id = ? AND status = 'pending'",
                [(int) $customer->getId()]
            );

            if ($existing) {
                return $result->setData([
                    'success' => true,
                    'message' => 'You already have a pending deletion request. An admin will process it shortly.',
                ]);
            }

            $connection->insert($table, [
                'customer_id'    => (int) $customer->getId(),
                'customer_email' => $customer->getEmail(),
                'customer_name'  => $customer->getName(),
                'store_id'       => (int) $customer->getStoreId(),
                'status'         => 'pending',
                'requested_at'   => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            return $result->setData([
                'success' => true,
                'message' => 'Your account deletion request has been submitted. An admin will review and process it shortly.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => 'Could not submit request: ' . $e->getMessage()]);
        }
    }
}
