<?php

declare(strict_types=1);

namespace MiniOrange\PDProtectPremium\Controller\Adminhtml\Support;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use MiniOrange\PDProtectPremium\Helper\Curl;

/**
 * Handles the Contact Us / Submit Query form POST from Account/support.phtml.
 * Mirrors SSO's Controller/Adminhtml/Support/Index.php:
 *   - validates email + query are not empty
 *   - calls Curl::submitContactUs()
 *   - redirects back with a Magento flash message (success or error)
 */
class SubmitQuery extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtectPremium::account';

    public function __construct(
        Context $context,
        private readonly Curl $curl
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setRefererUrl();

        $email = trim((string) $this->getRequest()->getParam('email', ''));
        $phone = trim((string) $this->getRequest()->getParam('phone', ''));
        $query = trim((string) $this->getRequest()->getParam('query', ''));

        if (empty($email) || empty($query)) {
            $this->messageManager->addErrorMessage('Email and query are required.');
            return $redirect;
        }

        $response = $this->curl->submitContactUs($email, $phone, $query);
        $status   = strtoupper((string) ($response['status'] ?? ''));

        if ($status === 'SUCCESS') {
            $this->messageManager->addSuccessMessage(
                'Support query submitted successfully. We will get back to you shortly.'
            );
        } else {
            $msg = $response['message'] ?? 'An error occurred while submitting your query. Please try again.';
            $this->messageManager->addErrorMessage((string) $msg);
        }

        return $redirect;
    }
}
