<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Admin\Routing;

use Softspring\CmsBundle\Routing\Provider\RoutingProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class AdminRoutingProvider implements RoutingProviderInterface
{
    public function supportedTypes(): array
    {
        return ['sfs_cms_plugin_admin'];
    }

    public function supports(string $type): bool
    {
        return \in_array($type, $this->supportedTypes(), true);
    }

    public function getAdminRoutes(string $type): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add('sfs_cms_admin_seo_dashboard', new Route('/seo', [
            '_controller' => 'sfs_cms.seo_plugin.admin.dashboard.controller::index',
        ]));
        $collection->add('sfs_cms_admin_seo_sites_analyze', new Route('/seo/sites/analyze', [
            '_controller' => 'sfs_cms.seo_plugin.admin.dashboard.controller::analyzeSites',
        ], [], [], '', [], ['POST']));
        $collection->add('sfs_cms_admin_seo_contents_analyze', new Route('/seo/contents/analyze', [
            '_controller' => 'sfs_cms.seo_plugin.admin.dashboard.controller::analyzeContents',
        ], [], [], '', [], ['POST']));

        return $collection;
    }
}
