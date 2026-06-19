<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class SitemapIndexReachableCheck implements SiteCheckInterface
{
    public function getCode(): string
    {
        return 'sitemap_index_reachable';
    }

    public function getPriority(): int
    {
        return 700;
    }

    public function check(SiteCheckContext $context): SeoCheck
    {
        $url = $context->sitemapIndexUrl();
        if (null === $url) {
            return new SeoCheck('sitemap_index_reachable', 'info', 100, [], [
                'url' => '-',
                'value' => 'sitemap index disabled',
            ]);
        }

        $resource = $context->fetch($url);
        if (null === $resource || null !== $resource->error) {
            return new SeoCheck('sitemap_index_reachable', 'error', 0, [], [
                'url' => $url,
                'error' => null === $resource ? 'Unknown error' : $resource->error,
            ]);
        }

        $details = [
            'url' => $url,
            'status_code' => (string) ($resource->statusCode ?? '-'),
            'content_type' => $resource->contentType ?? '-',
        ];

        if (!$resource->isSuccessful()) {
            return new SeoCheck('sitemap_index_reachable', 'error', 0, ['statusCode' => $resource->statusCode], $details);
        }

        if (!$resource->isXml()) {
            return new SeoCheck('sitemap_index_reachable', 'warning', 70, ['contentType' => $resource->contentType], $details);
        }

        return new SeoCheck('sitemap_index_reachable', 'ok', 100, ['statusCode' => $resource->statusCode], $details);
    }
}
