<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Admin\Menu;

use Softspring\CmsBundle\Admin\Menu\AbstractContentMenuProvider;
use Softspring\CmsBundle\Admin\Menu\MenuHelper;
use Softspring\CmsBundle\Admin\Menu\MenuItem;
use Softspring\CmsBundle\Config\Exception\InvalidContentException;

class ContentMenuProvider extends AbstractContentMenuProvider
{
    public static function getPriority(): int
    {
        return 251;
    }

    /**
     * @throws InvalidContentException
     */
    public function getMenu(array $menu, ?string $currentSelection = null, ?object $entity = null): array
    {
        [$content, $contentType] = $this->getContent(['content' => $entity]);

        $item = new MenuItem(
            'seo-analysis',
            $this->translator->trans('tabs_menu.seo', [], 'sfs_cms_seo'),
            $this->router->generate("sfs_cms_admin_content_{$contentType}_seo_analysis", ['content' => $content->getId()]),
            'seo-analysis' === $currentSelection,
            false,
        );

        $index = MenuHelper::getMenuIndex('routes', $menu);
        if (false === $index) {
            $menu[] = $item;

            return $menu;
        }

        return array_merge(array_slice($menu, 0, $index), [$item], array_slice($menu, $index));
    }
}
