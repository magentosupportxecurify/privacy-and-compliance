<?php

declare(strict_types=1);

namespace MiniOrange\CookieConsent\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MiniOrange\CookieConsent\Helper\Data as PrivacyHelper;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'MiniOrange_CookieConsent::config';

    protected function _isAllowed()
    {
        if ($this->_authorization->isAllowed('MiniOrange_CookieConsent::config')) {
            return true;
        }
        $post = $this->getRequest()->getPostValue();
        $tab = is_array($post) && isset($post['active_tab']) ? (string) $post['active_tab'] : 'banner_settings';

        return $this->_authorization->isAllowed(Index::aclResourceForTab($tab));
    }

    public function __construct(
        Context $context,
        private readonly PrivacyHelper $privacyHelper
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please refresh the page.'));
            return $redirect->setPath(self::pathForTab((string) $this->getRequest()->getParam('active_tab', 'banner_settings')));
        }

        $post = $this->getRequest()->getPostValue();
        $tab = $post['active_tab'] ?? 'banner_settings';
        $showSuccess = true;

        if (isset($post['general'])) {
            $g = $post['general'];
            $newBannerEnabled = (int) ($g['enabled'] ?? 0);

            if ($newBannerEnabled === 0 && $this->privacyHelper->isScriptBlockerEnabled()) {
                $this->privacyHelper->setStoreConfig('script_manager/enabled', 0);
                $this->messageManager->addWarningMessage(
                    __(
                        'Script Blocker was disabled because the Cookie consent banner was turned off. '
                        . 'Enable the banner again before enabling Script Blocker.'
                    )
                );
            }

            $this->privacyHelper->setStoreConfig('general/enabled', $newBannerEnabled);
            $this->privacyHelper->setStoreConfig('general/position', $g['position'] ?? 'bottom');
            $this->privacyHelper->setStoreConfig('general/banner_title', $g['banner_title'] ?? '');
            $this->privacyHelper->setStoreConfig('general/banner_body', $g['banner_body'] ?? '');
            $this->privacyHelper->setStoreConfig('general/privacy_url', $g['privacy_url'] ?? '');
        }

        if (isset($post['script_manager'])) {
            $sm = $post['script_manager'];
            $bannerOn = $this->privacyHelper->isBannerEnabled();
            $wantBlocker = (int) ($sm['enabled'] ?? 0);

            if ($wantBlocker === 1 && !$bannerOn) {
                $this->messageManager->addErrorMessage(
                    __('Enable the Cookie consent banner before enabling Script Blocker.')
                );
                $wantBlocker = 0;
                $showSuccess = false;
            }

            if (!$bannerOn) {
                $wantBlocker = 0;
            }

            $this->privacyHelper->setStoreConfig('script_manager/enabled', $wantBlocker);
            if (array_key_exists('custom_patterns', $sm)) {
                $this->privacyHelper->setStoreConfig('script_manager/custom_patterns', $sm['custom_patterns'] ?? '');
            }

            if (isset($sm['detected'])) {
                $detectedMap = $this->parseDetectedPost($sm['detected']);
                $this->privacyHelper->setStoreConfig('script_manager/discovered_map', (string) json_encode($detectedMap));
            }
        }

        $this->privacyHelper->flushCache('Save::execute');

        if ($showSuccess) {
            $this->messageManager->addSuccessMessage(__('Settings saved successfully.'));
        }

        return $redirect->setPath(self::pathForTab($tab));
    }

    private static function pathForTab(string $tab): string
    {
        return match ($tab) {
            'script_manager' => 'mocookieconsent/scriptmanager/index',
            'upgrade' => 'mocookieconsent/upgrade/index',
            default => 'mocookieconsent/general/index',
        };
    }

    /**
     * @param mixed $detected
     * @return array<string, string>
     */
    private function parseDetectedPost(mixed $detected): array
    {
        if (!is_array($detected)) {
            return [];
        }

        $valid = ['necessary', 'analytics', 'marketing', 'functional'];
        $map = [];
        foreach ($detected as $row) {
            if (!is_array($row)) {
                continue;
            }
            $url = isset($row['url']) ? trim((string) $row['url']) : '';
            $category = isset($row['category']) ? strtolower(trim((string) $row['category'])) : '';
            if ($url === '' || !in_array($category, $valid, true)) {
                continue;
            }
            $map[strtolower($url)] = $category;
        }

        ksort($map);

        return $map;
    }
}
