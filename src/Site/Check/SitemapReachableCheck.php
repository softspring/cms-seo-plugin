<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class SitemapReachableCheck implements SiteCheckInterface
{
    public function getCode(): string
    {
        return 'sitemap_reachable';
    }

    public function getPriority(): int
    {
        return 500;
    }

    public function check(SiteCheckContext $context): SeoCheck
    {
        $sitemapUrls = $context->configuredSitemapUrls();
        if ([] === $sitemapUrls) {
            return new SeoCheck('sitemap_reachable', 'info', 100, ['sitemaps' => 0], [
                'values' => [],
                'errors' => [],
            ]);
        }

        $errors = [];
        foreach ($sitemapUrls as $sitemapUrl) {
            $resource = $context->fetch($sitemapUrl);
            if (null === $resource || null !== $resource->error) {
                $errors[] = $sitemapUrl;
                continue;
            }

            if (!$resource->isSuccessful() || !$resource->isXml()) {
                $errors[] = $sitemapUrl;
            }
        }

        $details = [
            'values' => $sitemapUrls,
            'errors' => $errors,
        ];

        if ([] !== $errors) {
            return new SeoCheck('sitemap_reachable', 'warning', 60, [
                'sitemaps' => \count($sitemapUrls),
                'errors' => \count($errors),
            ], $details);
        }

        return new SeoCheck('sitemap_reachable', 'ok', 100, [
            'sitemaps' => \count($sitemapUrls),
            'errors' => 0,
        ], $details);
    }
}
