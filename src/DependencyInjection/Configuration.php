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
                ->arrayNode('word_statistics')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('limit')->defaultValue(20)->min(1)->max(100)->end()
                        ->integerNode('min_word_length')->defaultValue(2)->min(1)->max(20)->end()
                        ->arrayNode('stop_words')
                            ->useAttributeAsKey('locale')
                            ->arrayPrototype()->scalarPrototype()->end()->end()
                        ->end()
                        ->arrayNode('weights')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('h1')->defaultValue(6)->min(1)->max(20)->end()
                                ->integerNode('h2')->defaultValue(5)->min(1)->max(20)->end()
                                ->integerNode('h3')->defaultValue(4)->min(1)->max(20)->end()
                                ->integerNode('h4')->defaultValue(3)->min(1)->max(20)->end()
                                ->integerNode('h5')->defaultValue(2)->min(1)->max(20)->end()
                                ->integerNode('h6')->defaultValue(2)->min(1)->max(20)->end()
                                ->integerNode('strong')->defaultValue(3)->min(1)->max(20)->end()
                                ->integerNode('em')->defaultValue(2)->min(1)->max(20)->end()
                                ->integerNode('mark')->defaultValue(3)->min(1)->max(20)->end()
                                ->integerNode('u')->defaultValue(2)->min(1)->max(20)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
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
