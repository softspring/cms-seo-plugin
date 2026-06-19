<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class RobotsSitemapIndexCheck implements SiteCheckInterface
{
    public function getCode(): string
    {
        return 'robots_sitemap_index';
    }

    public function getPriority(): int
    {
        return 800;
    }

    public function check(SiteCheckContext $context): SeoCheck
    {
        $sitemapIndexUrl = $context->sitemapIndexUrl();
        if (null === $sitemapIndexUrl) {
            return new SeoCheck('robots_sitemap_index', 'info', 100, [], [
                'expected' => '-',
                'value' => 'sitemap index disabled',
            ]);
        }

        $robotsUrl = $context->robotsUrl();
        $resource = $context->fetch($robotsUrl);
        if (null === $resource || null !== $resource->error || !$resource->isSuccessful()) {
            return new SeoCheck('robots_sitemap_index', 'warning', 50, [], [
                'url' => $robotsUrl ?? '-',
                'expected' => $sitemapIndexUrl,
                'error' => $resource?->error ?? 'robots.txt is not reachable',
            ]);
        }

        $content = $resource->content ?? '';
        $found = preg_match('/^Sitemap:\s*(.+)$/mi', $content, $matches) ? trim($matches[1]) : '';
        $details = [
            'url' => $robotsUrl ?? '-',
            'expected' => $sitemapIndexUrl,
            'value' => '' !== $found ? $found : '-',
        ];

        if (!str_contains($content, 'Sitemap: '.$sitemapIndexUrl)) {
            return new SeoCheck('robots_sitemap_index', 'warning', 50, ['expected' => $sitemapIndexUrl], $details);
        }

        return new SeoCheck('robots_sitemap_index', 'ok', 100, ['sitemap' => $sitemapIndexUrl], $details);
    }
}
