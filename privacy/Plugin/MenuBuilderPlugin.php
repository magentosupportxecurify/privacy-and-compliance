<?php
namespace MiniOrange\Privacy\Plugin;

use Magento\Framework\Module\Manager;

class MenuBuilderPlugin
{
    /** @var Manager */
    private Manager $moduleManager;

    /**
     * @param Manager $moduleManager
     */
    public function __construct(Manager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Remove menu items when Privacy module is disabled.
     *
     * @param \Magento\Backend\Model\Menu\Builder $subject
     * @param \Magento\Backend\Model\Menu $menu
     * @return \Magento\Backend\Model\Menu
     */
    public function afterGetResult(\Magento\Backend\Model\Menu\Builder $subject, $menu)
    {
        if (!$this->moduleManager->isEnabled('MiniOrange_Privacy')) {
            return $menu;
        }

        $menusToHide = [
            'MiniOrange_CookieConsent::privacy',
            'MiniOrange_PDProtect::pdprotect',
        ];

        foreach ($menusToHide as $menuId) {
            if ($menu->get($menuId)) {
                $menu->remove($menuId);
            }
        }

        return $menu;
    }
}
