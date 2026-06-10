<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Helper;

/**
 * Curl adapter with peer verification disabled (same pattern as other miniOrange Magento modules).
 */
class MoCurl extends \Magento\Framework\HTTP\Adapter\Curl
{
    public function __construct()
    {
        $this->_config['verifypeer'] = false;
        $this->_config['verifyhost'] = false;
        $this->_config['header'] = false;
    }
}
