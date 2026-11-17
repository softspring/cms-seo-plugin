<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Twig;

use InvalidArgumentException;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsSeoPlugin\Content\WordStatistics\VersionWordStatistics;
use Twig\Extension\RuntimeExtensionInterface;

class WordStatisticsRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly VersionWordStatistics $statistics) {}

    public function analyze(ContentVersionInterface $version, ?string $locale = null, ?string $site = null, ?int $limit = null): array
    {
        try {
            return $this->statistics->analyze($version, $locale, $site, $limit);
        } catch (InvalidArgumentException $exception) {
            return ['ok' => false, 'error' => $exception->getMessage()];
        }
    }
}
