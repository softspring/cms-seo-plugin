<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin;

use Softspring\CmsBundle\DependencyInjection\Compiler\AddTwigBundlesNamespacesPass;
use Softspring\CmsBundle\Plugin\SfsCmsPlugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SfsCmsSeoPlugin extends SfsCmsPlugin
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public static function getAlias(): string
    {
        return 'sfs_cms_seo';
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AddTwigBundlesNamespacesPass($this->getPath() . '/templates'));
    }
}
