<?php
namespace MiniOrange\Privacy\Controller\Adminhtml\Redirect;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Module\Manager;
use Magento\Backend\Model\UrlInterface;

class Index extends Action
{
    /** @var Manager */
    private Manager $moduleManager;

    /** @var UrlInterface */
    private UrlInterface $backendUrl;

    /**
     * @param Context $context
     * @param Manager $moduleManager
     * @param UrlInterface $backendUrl
     */
    public function __construct(Context $context, Manager $moduleManager, UrlInterface $backendUrl)
    {
        parent::__construct($context);
        $this->moduleManager = $moduleManager;
        $this->backendUrl = $backendUrl;
    }

    /**
     * Execute redirect action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $module = $this->getRequest()->getParam('module');

        switch ($module) {
            case 'cookieconsent':
                return $this->redirectToCookieConsent();
            case 'pdprotect':
                return $this->redirectToPDProtect();
            default:
                $this->messageManager->addErrorMessage(__('Invalid module specified.'));
                return $this->_redirect('adminhtml/dashboard/index');
        }
    }

    /**
     * Redirect to Cookie Consent module.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirectToCookieConsent()
    {
        if ($this->moduleManager->isEnabled('MiniOrange_CookieConsent')) {
            return $this->_redirect($this->backendUrl->getUrl('mocookieconsent/general/index'));
        }
        $this->messageManager->addErrorMessage(__('Cookie Consent module is not installed.'));
        return $this->_redirect('adminhtml/dashboard/index');
    }

    /**
     * Redirect to PDProtect module.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    private function redirectToPDProtect()
    {
        if ($this->moduleManager->isEnabled('MiniOrange_PDProtectPremium')) {
            return $this->_redirect($this->backendUrl->getUrl('mopdp/account/index'));
        }
        if ($this->moduleManager->isEnabled('MiniOrange_PDProtect')) {
            return $this->_redirect($this->backendUrl->getUrl('mopdp/generalsettings/index'));
        }
        $this->messageManager->addErrorMessage(__('Personal Data Protection module is not installed.'));
        return $this->_redirect('adminhtml/dashboard/index');
    }

    /**
     * Check if action is allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MiniOrange_Privacy::Privacy');
    }
}
