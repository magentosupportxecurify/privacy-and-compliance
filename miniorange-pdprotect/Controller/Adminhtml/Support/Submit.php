<?php
declare(strict_types=1);

namespace MiniOrange\PDProtect\Controller\Adminhtml\Support;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use MiniOrange\PDProtect\Helper\Curl;

/**
 * Admin AJAX endpoint for the floating support form.
 *
 * Route: GET /admin/mopdp/support/submit
 * Accepts: email (required), phone (optional), query (required)
 * Returns: JSON { success: bool, message: string }
 */
class Submit extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_PDProtect::config';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $email = trim((string) $this->getRequest()->getParam('email', ''));
            $phone = trim((string) $this->getRequest()->getParam('phone', ''));
            $query = trim((string) $this->getRequest()->getParam('query', ''));

            if ($email === '') {
                return $result->setData([
                    'success' => false,
                    'message' => 'Email is required. Please enter a valid email address.',
                ]);
            }

            if ($query === '') {
                return $result->setData([
                    'success' => false,
                    'message' => 'Query is required. Please describe your issue.',
                ]);
            }

            Curl::submit_contact_us($email, $phone, $query);

            return $result->setData([
                'success' => true,
                'message' => 'Thanks for your inquiry. We will get back shortly via email.'
                    . ' If you don\'t hear from us within 24 hours, please send a follow-up'
                    . ' email to magentosupport@xecurify.com.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ]);
        }
    }
}
