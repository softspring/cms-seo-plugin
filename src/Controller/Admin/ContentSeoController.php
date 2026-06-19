<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Controller\Admin;

use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Helper\CmsHelper;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Manager\ContentVersionManagerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsSeoPlugin\Content\BasicSeoAnalyzer;
use Softspring\CmsSeoPlugin\Content\PageUrl;
use Softspring\CmsSeoPlugin\Content\PageUrlResolver;
use Softspring\CmsSeoPlugin\Content\PublishedPage;
use Softspring\CmsSeoPlugin\Content\PublishedPageFetcher;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisResult;
use Softspring\CmsSeoPlugin\Seo\SeoCheck;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentSeoController extends AbstractController
{
    private const META_FIELD = 'sfs_cms_seo_analysis';

    public function __construct(
        protected CmsConfig $cmsConfig,
        protected ContentManagerInterface $contentManager,
        protected ContentVersionManagerInterface $contentVersionManager,
        protected CmsHelper $cmsHelper,
        protected PageUrlResolver $pageUrlResolver,
        protected PublishedPageFetcher $publishedPageFetcher,
        protected BasicSeoAnalyzer $basicSeoAnalyzer,
    ) {}

    public function seo(Request $request): Response
    {
        $content = $this->loadContent($request);
        $contentType = (string) $request->attributes->get('_content_type');
        $targetVersion = $this->loadTargetVersion($request, $content);
        $storedAnalysis = $this->loadStoredAnalysis($targetVersion);
        $refresh = $request->isMethod('POST') && $request->request->getBoolean('refresh');
        $compiledContentWarning = $refresh && $this->contentSaveCompiled($targetVersion);
        $rows = [];
        $summary = \is_array($storedAnalysis['summary'] ?? null) ? $storedAnalysis['summary'] : $this->emptySummary();
        $loadedFromStorage = null !== $storedAnalysis && !$refresh;

        if ($loadedFromStorage) {
            $rows = \is_array($storedAnalysis['rows'] ?? null) ? $storedAnalysis['rows'] : [];
        } else {
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

            if ($targetVersion instanceof ContentVersionInterface) {
                $storedAnalysis = $this->storeAnalysis($targetVersion, $content, $rows, $summary);
            }
        }

        return $this->render('@SfsCmsSeoPlugin/admin/content/seo.html.twig', [
            'content' => $content,
            'entity' => $content,
            'content_entity' => $content,
            'content_type' => $contentType,
            'content_config' => $this->cmsConfig->getContent($contentType),
            'target_version' => $targetVersion,
            'stored_analysis' => $storedAnalysis,
            'loaded_from_storage' => $loadedFromStorage,
            'compiled_content_warning' => $compiledContentWarning,
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }

    private function loadContent(Request $request): ContentInterface
    {
        $contentType = (string) $request->attributes->get('_content_type');
        $contentId = (string) $request->attributes->get('content');

        if ('' === $contentType || '' === $contentId) {
            throw new NotFoundHttpException('Content not found.');
        }

        $content = $this->contentManager->getRepository($contentType)->findOneBy(['id' => $contentId]);
        if (!$content instanceof ContentInterface) {
            throw new NotFoundHttpException('Content not found.');
        }

        return $content;
    }

    private function loadTargetVersion(Request $request, ContentInterface $content): ?ContentVersionInterface
    {
        $versionId = (string) $request->attributes->get('version', '');
        if ('' === $versionId) {
            return $content->getPublishedVersion();
        }

        $version = $this->contentVersionManager->getRepository()->findOneBy(['id' => $versionId]);
        if (!$version instanceof ContentVersionInterface || $version->getContent()?->getId() !== $content->getId()) {
            throw new NotFoundHttpException('Content version not found.');
        }

        return $version;
    }

    /**
     * @return array{score: int, errors: int, warnings: int, urls: int}
     */
    private function emptySummary(): array
    {
        return [
            'score' => 0,
            'errors' => 0,
            'warnings' => 0,
            'urls' => 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadStoredAnalysis(?ContentVersionInterface $version): ?array
    {
        if (!$version instanceof ContentVersionInterface) {
            return null;
        }

        $analysis = $version->getMetaField(self::META_FIELD);

        return \is_array($analysis) ? $analysis : null;
    }

    private function contentSaveCompiled(?ContentVersionInterface $version): bool
    {
        if (!$version instanceof ContentVersionInterface) {
            return false;
        }

        try {
            return $this->cmsHelper->compile()->contentSaveCompiled($version);
        } catch (\Throwable) {
            return false;
        }
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
            'score' => [] === $rows ? $this->emptySummary()['score'] : (int) round($score / \count($rows)),
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
    private function storeAnalysis(ContentVersionInterface $version, ContentInterface $content, array $rows, array $summary): array
    {
        $analysis = [
            'schema' => 1,
            'analyzedAt' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            'contentId' => (string) $content->getId(),
            'versionId' => (string) $version->getId(),
            'versionNumber' => $version->getVersionNumber(),
            'summary' => $summary,
            'rows' => array_map(fn(array $row): array => $this->serializeRow($row), $rows),
        ];

        $version->setMetaField(self::META_FIELD, $analysis);
        $this->contentVersionManager->saveEntity($version);

        return $analysis;
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
            'analysis' => $this->serializeAnalysis($row['analysis']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAnalysis(SeoAnalysisResult $analysis): array
    {
        return [
            'score' => $analysis->score,
            'severity' => $analysis->severity,
            'metrics' => $analysis->metrics,
            'sources' => $analysis->sources,
            'checks' => array_map(fn(SeoCheck $check): array => $this->serializeCheck($check), $analysis->checks),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCheck(SeoCheck $check): array
    {
        return [
            'code' => $check->code,
            'severity' => $check->severity,
            'score' => $check->score,
            'data' => $check->data,
            'details' => $check->details,
        ];
    }
}
