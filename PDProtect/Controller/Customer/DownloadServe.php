<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use MiniOrange\PDProtect\Helper\Data as PDProtectHelper;

/**
 * Free-tier stub — personal data download is a Premium feature.
 *
 * The full implementation lives in the PDProtectPremium extension,
 * which overrides this class via DI preference when installed.
 */
class DownloadServe implements HttpGetActionInterface
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly RedirectFactory $redirectFactory,
        private readonly PDProtectHelper $dataHelper
    ) {}

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }

        if (!$this->dataHelper->isCustomerDataControlsFunctional()) {
            return $this->redirectFactory->create()->setPath('mopdp/customer/privacy');
        }

        return $this->redirectFactory->create()->setPath('mopdp/customer/privacy');
    }
}
