<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class SitemapIndexContainsConfiguredSitemapsCheck implements SiteCheckInterface
{
    public function getCode(): string
    {
        return 'sitemap_index_contains_configured_sitemaps';
    }

    public function getPriority(): int
    {
        return 600;
    }

    public function check(SiteCheckContext $context): SeoCheck
    {
        $sitemapIndexUrl = $context->sitemapIndexUrl();
        $expectedUrls = $context->configuredSitemapUrls();
        if (null === $sitemapIndexUrl || [] === $expectedUrls) {
            return new SeoCheck('sitemap_index_contains_configured_sitemaps', 'info', 100, ['expected' => \count($expectedUrls)], [
                'url' => $sitemapIndexUrl ?? '-',
                'expected' => [] !== $expectedUrls ? $expectedUrls : ['-'],
                'missing' => [],
            ]);
        }

        $resource = $context->fetch($sitemapIndexUrl);
        if (null === $resource || null !== $resource->error || !$resource->isSuccessful() || null === $resource->content) {
            return new SeoCheck('sitemap_index_contains_configured_sitemaps', 'warning', 50, ['expected' => \count($expectedUrls)], [
                'url' => $sitemapIndexUrl,
                'expected' => $expectedUrls,
                'missing' => $expectedUrls,
                'error' => null === $resource ? 'sitemap index is not reachable' : ($resource->error ?? 'sitemap index is not reachable'),
            ]);
        }

        $foundUrls = $this->extractLocUrls($resource->content);
        $missingUrls = array_values(array_filter($expectedUrls, static fn(string $expectedUrl): bool => !\in_array($expectedUrl, $foundUrls, true)));
        $details = [
            'url' => $sitemapIndexUrl,
            'expected' => $expectedUrls,
            'missing' => [] !== $missingUrls ? $missingUrls : [],
        ];

        if ([] !== $missingUrls) {
            return new SeoCheck('sitemap_index_contains_configured_sitemaps', 'warning', 60, [
                'expected' => \count($expectedUrls),
                'missing' => \count($missingUrls),
            ], $details);
        }

        return new SeoCheck('sitemap_index_contains_configured_sitemaps', 'ok', 100, [
            'expected' => \count($expectedUrls),
            'missing' => 0,
        ], $details);
    }

    /**
     * @return string[]
     */
    private function extractLocUrls(string $xml): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, \LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return [];
        }

        $urls = [];
        foreach ($document->getElementsByTagName('loc') as $loc) {
            $value = trim($loc->textContent);
            if ('' !== $value) {
                $urls[] = $value;
            }
        }

        return array_values(array_unique($urls));
    }
}
