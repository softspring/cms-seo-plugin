<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site;

use Softspring\CmsBundle\Model\SiteInterface;
use Softspring\CmsSeoPlugin\Site\PublicResourceFetcher;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisResult;
use Softspring\CmsSeoPlugin\Seo\SeoCheck;
use Softspring\CmsSeoPlugin\Site\Check\SiteCheckContext;
use Softspring\CmsSeoPlugin\Site\Check\SiteChecksLoader;

class SiteAnalyzer
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly SiteChecksLoader $checksLoader,
        private readonly PublicResourceFetcher $fetcher,
        private readonly array $config,
    ) {}

    public function analyze(SiteInterface $site): SeoAnalysisResult
    {
        if (false === ($this->config['enabled'] ?? true)) {
            return new SeoAnalysisResult(0, 'ok', [], ['enabled' => false]);
        }

        $context = new SiteCheckContext($site, $this->fetcher);
        $checks = [];

        foreach ($this->checksLoader->getChecks() as $check) {
            if (!$this->isCheckEnabled($check->getCode())) {
                continue;
            }

            $checks[] = $check->check($context);
        }

        return new SeoAnalysisResult(
            $this->score($checks),
            $this->severity($checks),
            $checks,
            [
                'checks' => \count($checks),
                'configuredSitemaps' => \count($context->configuredSitemapUrls()),
            ],
            ['site'],
        );
    }

    private function isCheckEnabled(string $code): bool
    {
        return false !== ($this->config['checks'][$code] ?? true);
    }

    /**
     * @param SeoCheck[] $checks
     */
    private function score(array $checks): int
    {
        if ([] === $checks) {
            return 0;
        }

        return (int) round(array_sum(array_map(static fn(SeoCheck $check): int => $check->score, $checks)) / \count($checks));
    }

    /**
     * @param SeoCheck[] $checks
     */
    private function severity(array $checks): string
    {
        foreach ($checks as $check) {
            if ('error' === $check->severity) {
                return 'error';
            }
        }

        foreach ($checks as $check) {
            if ('warning' === $check->severity) {
                return 'warning';
            }
        }

        return 'ok';
    }
}
