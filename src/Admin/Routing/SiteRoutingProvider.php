<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Admin\Routing;

use Softspring\CmsBundle\Routing\Provider\RoutingProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SiteRoutingProvider implements RoutingProviderInterface
{
    public function supportedTypes(): array
    {
        return ['sfs_cms_plugin_admin_site'];
    }

    public function supports(string $type): bool
    {
        return \in_array($type, $this->supportedTypes(), true);
    }

    public function getAdminRoutes(string $type): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add('sfs_cms_admin_sites_seo', new Route('/{site}/seo', [
            '_controller' => 'sfs_cms.seo_plugin.admin.site_seo.controller::seo',
        ]));

        return $collection;
    }
}
