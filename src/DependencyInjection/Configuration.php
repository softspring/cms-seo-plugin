<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sfs_cms_seo');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('site_analysis')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->arrayNode('checks')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('robots_reachable')->defaultTrue()->end()
                                ->booleanNode('robots_sitemap_index')->defaultTrue()->end()
                                ->booleanNode('sitemap_index_reachable')->defaultTrue()->end()
                                ->booleanNode('sitemap_index_contains_configured_sitemaps')->defaultTrue()->end()
                                ->booleanNode('sitemap_reachable')->defaultTrue()->end()
                                ->booleanNode('slash_route_redirect')->defaultTrue()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
