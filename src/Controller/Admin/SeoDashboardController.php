<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Controller\Admin;

use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Manager\ContentManagerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsSeoPlugin\Content\ContentSeoAnalysisRunner;
use Softspring\CmsSeoPlugin\Site\SiteAnalysisRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SeoDashboardController extends AbstractController
{
    private const META_FIELD = 'sfs_cms_seo_analysis';
    private const WORST_LIMIT = 20;

    public function __construct(
        protected CmsConfig $cmsConfig,
        protected ContentManagerInterface $contentManager,
        protected RouterInterface $router,
        protected SiteAnalysisRunner $siteAnalysisRunner,
        protected ContentSeoAnalysisRunner $contentAnalysisRunner,
    ) {}

    public function index(): Response
    {
        $items = $this->collectItems();
        $stats = $this->buildStats($items);
        $worstContents = $this->buildWorstContents($items);
        $worstUrls = $this->buildWorstUrls($items);
        $siteRows = $this->buildSiteRows($this->siteAnalysisRunner->storedRows());
        $siteStats = $this->buildSiteStats($siteRows);

        return $this->render('@SfsCmsSeoPlugin/admin/seo/dashboard.html.twig', [
            'stats' => $stats,
            'worst_contents' => $worstContents,
            'worst_urls' => $worstUrls,
            'site_stats' => $siteStats,
            'site_rows' => $siteRows,
        ]);
    }

    public function analyzeSites(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('sfs_cms_seo_analyze_sites', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->siteAnalysisRunner->analyzeAll();

        return $this->redirectToRoute('sfs_cms_admin_seo_dashboard');
    }

    public function analyzeContents(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('sfs_cms_seo_analyze_contents', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->contentAnalysisRunner->analyzeAll();

        return $this->redirectToRoute('sfs_cms_admin_seo_dashboard');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectItems(): array
    {
        $items = [];

        foreach ($this->cmsConfig->getContents() as $type => $config) {
            if (empty($config['entity_class']) || !$this->hasAdminContentRoute($type, 'details')) {
                continue;
            }

            $contents = $this->contentManager->getRepository($type)->createQueryBuilder('content')
                ->andWhere('content.publishedVersion IS NOT NULL')
                ->getQuery()
                ->getResult();

            foreach ($contents as $content) {
                if (!$content instanceof ContentInterface) {
                    continue;
                }

                $version = $content->getPublishedVersion();
                $analysis = $version?->getMetaField(self::META_FIELD);

                $items[] = [
                    'type' => (string) $type,
                    'content' => $content,
                    'version' => $version,
                    'analysis' => \is_array($analysis) ? $analysis : null,
                    'detailsRoute' => "sfs_cms_admin_content_{$type}_details",
                    'seoRoute' => $this->hasAdminContentRoute($type, 'seo_analysis') ? "sfs_cms_admin_content_{$type}_seo_analysis" : null,
                ];
            }
        }

        return $items;
    }

    private function hasAdminContentRoute(string $type, string $action): bool
    {
        return null !== $this->router->getRouteCollection()->get("sfs_cms_admin_content_{$type}_{$action}");
    }

    /**
     * @param array<int, array<string, mixed>> $siteRows
     * @return array<int, array<string, mixed>>
     */
    private function buildSiteRows(array $siteRows): array
    {
        foreach ($siteRows as &$row) {
            $row['errors'] = 0;
            $row['warnings'] = 0;

            foreach (($row['analysis']['checks'] ?? []) as $check) {
                if (!\is_array($check)) {
                    continue;
                }

                if ('error' === ($check['severity'] ?? null)) {
                    ++$row['errors'];
                } elseif ('warning' === ($check['severity'] ?? null)) {
                    ++$row['warnings'];
                }
            }
        }
        unset($row);

        return $siteRows;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, int|float>
     */
    private function buildStats(array $items): array
    {
        $published = \count($items);
        $analyzed = 0;
        $scoreSum = 0;
        $urls = 0;
        $errors = 0;
        $warnings = 0;

        foreach ($items as $item) {
            $summary = $item['analysis']['summary'] ?? null;
            if (!\is_array($summary)) {
                continue;
            }

            ++$analyzed;
            $scoreSum += (int) ($summary['score'] ?? 0);
            $urls += (int) ($summary['urls'] ?? 0);
            $errors += (int) ($summary['errors'] ?? 0);
            $warnings += (int) ($summary['warnings'] ?? 0);
        }

        return [
            'published' => $published,
            'analyzed' => $analyzed,
            'pending' => max(0, $published - $analyzed),
            'averageScore' => $analyzed > 0 ? round($scoreSum / $analyzed, 1) : 0,
            'urls' => $urls,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildWorstContents(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $summary = $item['analysis']['summary'] ?? null;
            if (!\is_array($summary)) {
                continue;
            }

            $rows[] = [
                'type' => $item['type'],
                'content' => $item['content'],
                'version' => $item['version'],
                'score' => (int) ($summary['score'] ?? 0),
                'errors' => (int) ($summary['errors'] ?? 0),
                'warnings' => (int) ($summary['warnings'] ?? 0),
                'urls' => (int) ($summary['urls'] ?? 0),
                'analyzedAt' => $item['analysis']['analyzedAt'] ?? null,
                'detailsRoute' => $item['detailsRoute'],
                'seoRoute' => $item['seoRoute'],
            ];
        }

        usort($rows, static fn(array $left, array $right): int => [$left['score'], -$left['errors'], -$left['warnings']] <=> [$right['score'], -$right['errors'], -$right['warnings']]);

        return \array_slice($rows, 0, self::WORST_LIMIT);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildWorstUrls(array $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $analysisRows = $item['analysis']['rows'] ?? [];
            if (!\is_array($analysisRows)) {
                continue;
            }

            foreach ($analysisRows as $analysisRow) {
                if (!\is_array($analysisRow)) {
                    continue;
                }

                $urlAnalysis = $analysisRow['analysis'] ?? [];
                $pageUrl = $analysisRow['pageUrl'] ?? [];
                if (!\is_array($urlAnalysis) || !\is_array($pageUrl)) {
                    continue;
                }

                $rows[] = [
                    'type' => $item['type'],
                    'content' => $item['content'],
                    'version' => $item['version'],
                    'score' => (int) ($urlAnalysis['score'] ?? 0),
                    'severity' => (string) ($urlAnalysis['severity'] ?? 'ok'),
                    'url' => (string) ($pageUrl['url'] ?? ''),
                    'path' => (string) ($pageUrl['path'] ?? ''),
                    'site' => (string) ($pageUrl['site'] ?? ''),
                    'locale' => (string) ($pageUrl['locale'] ?? ''),
                    'detailsRoute' => $item['detailsRoute'],
                    'seoRoute' => $item['seoRoute'],
                ];
            }
        }

        usort($rows, static fn(array $left, array $right): int => [$left['score'], $left['severity']] <=> [$right['score'], $right['severity']]);

        return \array_slice($rows, 0, self::WORST_LIMIT);
    }

    /**
     * @param array<int, array<string, mixed>> $siteRows
     * @return array{sites: int, analyzed: int, averageScore: float|int, errors: int, warnings: int}
     */
    private function buildSiteStats(array $siteRows): array
    {
        $analyzed = 0;
        $scoreSum = 0;
        $errors = 0;
        $warnings = 0;

        foreach ($siteRows as $row) {
            $analysis = $row['analysis'] ?? [];
            if (!\is_array($analysis) || empty($row['analyzedAt'])) {
                continue;
            }

            ++$analyzed;
            $scoreSum += (int) ($analysis['score'] ?? 0);

            foreach (($analysis['checks'] ?? []) as $check) {
                if (!\is_array($check)) {
                    continue;
                }

                if ('error' === ($check['severity'] ?? null)) {
                    ++$errors;
                } elseif ('warning' === ($check['severity'] ?? null)) {
                    ++$warnings;
                }
            }
        }

        return [
            'sites' => \count($siteRows),
            'analyzed' => $analyzed,
            'averageScore' => $analyzed > 0 ? round($scoreSum / $analyzed, 1) : 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
