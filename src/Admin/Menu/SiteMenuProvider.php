<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Admin\Menu;

use Softspring\CmsBundle\Admin\Menu\AbstractSiteMenuProvider;
use Softspring\CmsBundle\Admin\Menu\MenuItem;
use Softspring\CmsBundle\Model\SiteInterface;

class SiteMenuProvider extends AbstractSiteMenuProvider
{
    public static function getPriority(): int
    {
        return 251;
    }

    public function getMenu(array $menu, ?string $currentSelection = null, ?object $entity = null): array
    {
        if (!$entity instanceof SiteInterface) {
            return $menu;
        }

        $item = new MenuItem(
            'seo',
            $this->translator->trans('tabs_menu.site_seo', [], 'sfs_cms_seo'),
            $this->router->generate('sfs_cms_admin_sites_seo', ['site' => $entity->getId()]),
            'seo' === $currentSelection,
            false,
        );

        $menu[] = $item;

        return $menu;
    }
}
