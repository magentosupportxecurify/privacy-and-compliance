<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Controller\Adminhtml\Scan;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MiniOrange\CookieConsent\Helper\Data as PrivacyHelper;
use MiniOrange\CookieConsent\Model\ScriptScanner;

class Run extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_CookieConsent::config';

    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed('MiniOrange_CookieConsent::config')) {
            return true;
        }

        return $this->_authorization->isAllowed('MiniOrange_CookieConsent::tab_script_manager');
    }

    public function __construct(
        Context $context,
        private readonly PrivacyHelper $privacyHelper,
        private readonly ScriptScanner $scriptScanner
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please refresh the page.'));
            return $redirect->setPath('mocookieconsent/scriptmanager/index');
        }

        try {
            $urls = $this->scriptScanner->scanStoreHomepage();
            $existing = $this->privacyHelper->getDiscoveredMap();
            $merged = [];
            foreach ($urls as $url) {
                $merged[$url] = $existing[$url] ?? 'necessary';
            }
            $this->privacyHelper->setStoreConfig('script_manager/discovered_map', (string) json_encode($merged));
            $this->messageManager->addSuccessMessage(
                __('Re-scan complete. Found %1 third-party script(s).', count($merged))
            );
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('Could not scan the storefront: %1', $e->getMessage()));
        }

        return $redirect->setPath('mocookieconsent/scriptmanager/index');
    }
}
