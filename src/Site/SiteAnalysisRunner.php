<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site;

use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Model\SiteInterface;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisResult;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisSerializer;

class SiteAnalysisRunner
{
    public function __construct(
        private readonly CmsConfig $cmsConfig,
        private readonly SiteAnalyzer $siteAnalyzer,
        private readonly SiteAnalysisStorage $storage,
        private readonly SeoAnalysisSerializer $serializer,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function analyzeAll(): array
    {
        $rows = [];

        foreach ($this->cmsConfig->getSites() as $site) {
            if (!$site instanceof SiteInterface) {
                continue;
            }

            $rows[] = $this->analyze($site);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyze(SiteInterface $site): array
    {
        $analysis = $this->siteAnalyzer->analyze($site);
        $payload = $this->serialize($site, $analysis);

        $this->storage->save((string) $site->getId(), $payload);

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function storedRows(): array
    {
        $rows = [];

        foreach ($this->cmsConfig->getSites() as $site) {
            if (!$site instanceof SiteInterface) {
                continue;
            }

            $siteId = (string) $site->getId();
            $rows[] = $this->storage->load($siteId) ?? $this->emptyPayload($site);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function stored(SiteInterface $site): array
    {
        return $this->storage->load((string) $site->getId()) ?? $this->emptyPayload($site);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SiteInterface $site, SeoAnalysisResult $analysis): array
    {
        return [
            'schema' => 1,
            'analyzedAt' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            'site' => [
                'id' => (string) $site->getId(),
                'host' => $site->getCanonicalHost(),
                'scheme' => $site->getCanonicalScheme(),
            ],
            'analysis' => $this->serializer->serializeAnalysis($analysis),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(SiteInterface $site): array
    {
        return [
            'schema' => 1,
            'analyzedAt' => null,
            'site' => [
                'id' => (string) $site->getId(),
                'host' => $site->getCanonicalHost(),
                'scheme' => $site->getCanonicalScheme(),
            ],
            'analysis' => [
                'score' => 0,
                'severity' => 'ok',
                'metrics' => [],
                'sources' => [],
                'checks' => [],
            ],
        ];
    }
}
