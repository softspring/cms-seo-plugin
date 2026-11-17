<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Environment;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Twig\TwigFunction;

class WordStatisticsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [new TwigFunction('sfs_cms_seo_word_statistics', static function (Environment $twig, ContentVersionInterface $version, ?string $locale = null, ?string $site = null, ?int $limit = null): array {
            return $twig->getRuntime(WordStatisticsRuntime::class)->analyze($version, $locale, $site, $limit);
        }, ['needs_environment' => true])];
    }
}
