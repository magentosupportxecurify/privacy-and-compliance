<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
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
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\SessionManagerInterface;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

class DownloadData implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly ResourceConnection $resource,
        private readonly SessionManagerInterface $session,
        private readonly Json $jsonSerializer,
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
            $data = $this->buildExport($customerId);
            // Store export in session so DownloadServe can serve it as a file
            $this->session->setData('pdprotect_download_payload_' . $customerId, $data);

            return $result->setData(['success' => true, 'message' => 'Ready.']);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => 'Could not prepare data: ' . $e->getMessage()]);
        }
    }

    private function buildExport(int $customerId): array
    {
        $connection = $this->resource->getConnection();

        // Customer profile
        $customer = $this->customerRepository->getById($customerId);
        $profile  = [
            'id'        => $customer->getId(),
            'email'     => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname'  => $customer->getLastname(),
            'dob'       => $customer->getDob(),
            'created_at' => $customer->getCreatedAt(),
        ];

        // Addresses
        $addrTable = $this->resource->getTableName('customer_address_entity');
        $addresses = $connection->fetchAll(
            "SELECT * FROM {$addrTable} WHERE parent_id = ?",
            [$customerId]
        );

        // Orders
        $orderTable = $this->resource->getTableName('sales_order');
        $orders = $connection->fetchAll(
            "SELECT entity_id, increment_id, status, grand_total, created_at FROM {$orderTable} WHERE customer_id = ?",
            [$customerId]
        );

        // Consent log
        $logTable = $this->resource->getTableName('miniorange_pdprotect_consent_log');
        $consents = $connection->fetchAll(
            "SELECT consent_status, created_at FROM {$logTable} WHERE customer_id = ?",
            [$customerId]
        );

        return [
            'profile'   => $profile,
            'addresses' => $addresses,
            'orders'    => $orders,
            'consents'  => $consents,
            'exported_at' => (new \DateTime())->format(\DateTime::ATOM),
        ];
    }
}
