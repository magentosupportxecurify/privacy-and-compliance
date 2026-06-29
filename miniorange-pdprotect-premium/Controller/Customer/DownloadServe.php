<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use MiniOrange\PDProtectPremium\Helper\Data as PremiumHelper;

class DownloadServe implements HttpGetActionInterface
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly RedirectFactory $redirectFactory,
        private readonly FileFactory $fileFactory,
        private readonly SessionManagerInterface $session,
        private readonly Json $jsonSerializer,
        private readonly PremiumHelper $premiumHelper
    ) {}

    public function execute()
    {
        if (!$this->premiumHelper->isModuleActive()) {
            return $this->redirectFactory->create()->setPath('mopdp/customer/privacy');
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setPath('customer/account/login');
        }

        $customerId = (int) $this->customerSession->getCustomerId();
        $key        = 'pdprotect_download_payload_' . $customerId;
        $data       = $this->session->getData($key);

        if (empty($data)) {
            $this->customerSession->addNotice(__('No data export found. Please try again.'));
            return $this->redirectFactory->create()->setPath('mopdp/customer/privacy');
        }

        // Clear session payload after serving
        $this->session->unsetData($key);

        $filename = 'my-personal-data-' . date('Y-m-d') . '.json';
        $content  = $this->jsonSerializer->serialize($data);

        return $this->fileFactory->create(
            $filename,
            $content,
            \Magento\Framework\App\Filesystem\DirectoryList::TMP,
            'application/json'
        );
    }
}
