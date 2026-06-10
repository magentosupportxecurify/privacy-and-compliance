<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Controller\Adminhtml\ScriptManager;

use MiniOrange\CookieConsent\Controller\Adminhtml\Index\Index as PrivacyIndex;

class Index extends PrivacyIndex
{
    public const ADMIN_RESOURCE = 'MiniOrange_CookieConsent::tab_script_manager';

    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed('MiniOrange_CookieConsent::config')) {
            return true;
        }

        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

    public function execute()
    {
        $this->getRequest()->setParam('active_tab', 'script_manager');

        return parent::execute();
    }
}
