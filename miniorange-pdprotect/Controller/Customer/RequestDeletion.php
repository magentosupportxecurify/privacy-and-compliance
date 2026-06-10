<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Customer;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Free-tier stub — account deletion requests are a Premium feature.
 *
 * The full implementation lives in the PDProtectPremium extension,
 * which overrides this class via DI preference when installed.
 */
class RequestDeletion implements HttpPostActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory
    ) {}

    public function execute()
    {
        $result = $this->jsonFactory->create();
        return $result->setData([
            'success' => false,
            'message' => 'This feature requires the PDProtect Premium extension.',
        ]);
    }
}
