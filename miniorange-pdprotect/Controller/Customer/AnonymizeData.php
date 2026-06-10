<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;

/**
 * Free-tier stub — data anonymization is a Premium feature.
 *
 * The full implementation lives in the PDProtectPremium extension,
 * which overrides this class via DI preference when installed.
 */
class AnonymizeData implements HttpPostActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly CustomerSession $customerSession,
        private readonly PDProtectHelper $dataHelper
    ) {}

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['success' => false, 'message' => 'Not logged in.']);
        }

        if (!$this->dataHelper->isCustomerDataControlsFunctional()) {
            return $result->setData([
                'success' => false,
                'message' => 'This feature requires the PDProtect Premium extension.',
            ]);
        }

        return $result->setData(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
    }
}
