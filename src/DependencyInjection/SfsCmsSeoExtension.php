<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\DependencyInjection;

use Softspring\CmsSeoPlugin\Content\Check\CheckInterface;
use Softspring\CmsSeoPlugin\Site\Check\SiteCheckInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SfsCmsSeoExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sfs_cms_seo.site_analysis', $config['site_analysis']);

        $container->registerForAutoconfiguration(CheckInterface::class)->addTag('sfs_cms_seo.check');
        $container->registerForAutoconfiguration(SiteCheckInterface::class)->addTag('sfs_cms_seo.site_check');

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config/services'));
        $loader->load('services.yaml');
    }
}
