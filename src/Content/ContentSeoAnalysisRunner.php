<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content;

use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Manager\ContentVersionManagerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisResult;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisSerializer;

class ContentSeoAnalysisRunner
{
    private const META_FIELD = 'sfs_cms_seo_analysis';

    public function __construct(
        private readonly CmsConfig $cmsConfig,
        private readonly ContentManagerInterface $contentManager,
        private readonly ContentVersionManagerInterface $contentVersionManager,
        private readonly PageUrlResolver $pageUrlResolver,
        private readonly PublishedPageFetcher $publishedPageFetcher,
        private readonly BasicSeoAnalyzer $basicSeoAnalyzer,
        private readonly SeoAnalysisSerializer $serializer,
    ) {
    }

    /**
     * @return array{contents: int, urls: int, errors: int, warnings: int}
     */
    public function analyzeAll(): array
    {
        $summary = [
            'contents' => 0,
            'urls' => 0,
            'errors' => 0,
            'warnings' => 0,
        ];

        foreach ($this->cmsConfig->getContents() as $type => $config) {
            if (empty($config['entity_class'])) {
                continue;
            }

            $contents = $this->contentManager->getRepository((string) $type)->createQueryBuilder('content')
                ->andWhere('content.publishedVersion IS NOT NULL')
                ->getQuery()
                ->getResult();

            foreach ($contents as $content) {
                if (!$content instanceof ContentInterface) {
                    continue;
                }

                $result = $this->analyzeContent($content);
                ++$summary['contents'];
                $summary['urls'] += $result['summary']['urls'];
                $summary['errors'] += $result['summary']['errors'];
                $summary['warnings'] += $result['summary']['warnings'];
            }
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    public function analyzeContent(ContentInterface $content): array
    {
        $version = $content->getPublishedVersion();
        $rows = [];

        foreach ($this->pageUrlResolver->resolve($content) as $pageUrl) {
            $page = $this->publishedPageFetcher->fetch($pageUrl);
            $analysis = $this->basicSeoAnalyzer->analyze($pageUrl, $page);

            $rows[] = [
                'pageUrl' => $pageUrl,
                'page' => $page,
                'analysis' => $analysis,
            ];
        }

        $summary = $this->buildSummary($rows);
        $payload = $this->storeAnalysis($version, $content, $rows, $summary);

        return $payload;
    }

    /**
     * @param array<int, array{analysis: SeoAnalysisResult}> $rows
     * @return array{score: int, errors: int, warnings: int, urls: int}
     */
    private function buildSummary(array $rows): array
    {
        $score = 0;
        $errors = 0;
        $warnings = 0;

        foreach ($rows as $row) {
            $analysis = $row['analysis'];
            $score += $analysis->score;

            foreach ($analysis->checks as $check) {
                if ('error' === $check->severity) {
                    ++$errors;
                } elseif ('warning' === $check->severity) {
                    ++$warnings;
                }
            }
        }

        return [
            'score' => [] === $rows ? 0 : (int) round($score / \count($rows)),
            'errors' => $errors,
            'warnings' => $warnings,
            'urls' => \count($rows),
        ];
    }

    /**
     * @param array<int, array{pageUrl: PageUrl, page: PublishedPage, analysis: SeoAnalysisResult}> $rows
     * @param array{score: int, errors: int, warnings: int, urls: int} $summary
     * @return array<string, mixed>
     */
    private function storeAnalysis(?ContentVersionInterface $version, ContentInterface $content, array $rows, array $summary): array
    {
        $payload = [
            'schema' => 1,
            'analyzedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'contentId' => (string) $content->getId(),
            'versionId' => null !== $version ? (string) $version->getId() : null,
            'versionNumber' => $version?->getVersionNumber(),
            'summary' => $summary,
            'rows' => array_map(fn (array $row): array => $this->serializeRow($row), $rows),
        ];

        if ($version instanceof ContentVersionInterface) {
            $version->setMetaField(self::META_FIELD, $payload);
            $this->contentVersionManager->saveEntity($version);
        }

        return $payload;
    }

    /**
     * @param array{pageUrl: PageUrl, page: PublishedPage, analysis: SeoAnalysisResult} $row
     * @return array<string, mixed>
     */
    private function serializeRow(array $row): array
    {
        return [
            'pageUrl' => [
                'site' => (string) $row['pageUrl']->site->getId(),
                'path' => $row['pageUrl']->path,
                'url' => $row['pageUrl']->url,
                'locale' => $row['pageUrl']->locale,
            ],
            'page' => [
                'url' => $row['page']->url,
                'statusCode' => $row['page']->statusCode,
                'contentType' => $row['page']->contentType,
                'error' => $row['page']->error,
            ],
            'analysis' => $this->serializer->serializeAnalysis($row['analysis']),
        ];
    }
}
