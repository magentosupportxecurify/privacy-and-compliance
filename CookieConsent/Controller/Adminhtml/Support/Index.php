<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Controller\Adminhtml\Support;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use MiniOrange\CookieConsent\Helper\Data as PrivacyHelper;
use MiniOrange\CookieConsent\Helper\SupportCurl;
use Psr\Log\LoggerInterface;

class Index extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MiniOrange_CookieConsent::support';
    public $resultJsonFactory;
    public $resultFactory;
    public $reinitableConfig;
    public $logger;
    public $privacyHelper;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResultFactory $resultFactory,
        ReinitableConfigInterface $reinitableConfig,
        LoggerInterface $logger,
        PrivacyHelper $privacyHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultFactory = $resultFactory;
        $this->reinitableConfig = $reinitableConfig;
        $this->logger = $logger;
        $this->privacyHelper = $privacyHelper;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $isAjax = $this->getRequest()->isXmlHttpRequest();

        try {
            $params = $this->getRequest()->getParams();
            if (isset($params['email'])) {
                $isEmpty = $this->checkIfRequiredFieldsEmpty(['email' => $params]);
                $email = (string) $params['email'];
                $phone = isset($params['phone']) ? (string) $params['phone'] : '';
                $query = isset($params['query']) ? (string) $params['query'] : '';
                $response = SupportCurl::submitContactUs($email, $phone, $query);
                $this->privacyHelper->log_debug('SupportCurl::submitContactUs response:',$response);
                if ($response !== null && trim($response) === 'Query submitted.') {
                    if ($isAjax) {
                        return $resultJson->setData(['success' => true, 'message' => 'Query sent successfully!']);
                    }
                    $this->messageManager->addSuccessMessage(__('Query sent successfully!'));
                } else {
                    $this->reinitableConfig->reinit();
                    if ($isAjax) {
                        return $resultJson->setData(['success' => false, 'message' => 'Query failed to send!']);
                    }
                    if ($isEmpty) {
                        $this->messageManager->addErrorMessage(__('Required fields are missing.'));
                    } else {
                        $this->messageManager->addErrorMessage(__('Query failed to send!'));
                    }
                }
            } elseif ($isAjax) {
                return $resultJson->setData(['success' => false, 'message' => 'Invalid request.']);
            }
        } catch (\Throwable $e) {
            $this->logger->error('MiniOrange Cookie Consent support: ' . $e->getMessage(), ['exception' => $e]);
            if ($isAjax) {
                return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
            }

            return $resultJson->setData(['message' => $e->getMessage()]);
        }
        if ($isAjax) {
            return $resultJson->setData(['success' => false, 'message' => 'Query failed to send!']);
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }

    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed(self::ADMIN_RESOURCE)) {
            return true;
        }

        return $this->_authorization->isAllowed('MiniOrange_CookieConsent::tab_banner_settings')
            || $this->_authorization->isAllowed('MiniOrange_CookieConsent::tab_script_manager')
            || $this->_authorization->isAllowed('MiniOrange_CookieConsent::tab_upgrade')
            || $this->_authorization->isAllowed('MiniOrange_CookieConsent::config');
    }

    /**
     * @param array<string, mixed> $array
     */
    private function checkIfRequiredFieldsEmpty(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (is_array($value)
                && (!array_key_exists($key, $value) || $this->isBlank($value[$key] ?? null))) {
                return true;
            }
            if ($this->isBlank($value)) {
                return true;
            }
        }

        return false;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || (is_string($value) && trim($value) === '');
    }
}
