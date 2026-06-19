<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content;

use Softspring\CmsSeoPlugin\Content\Check\AlternatesCheck;
use Softspring\CmsSeoPlugin\Content\Check\CanonicalCheck;
use Softspring\CmsSeoPlugin\Content\Check\ChecksLoader;
use Softspring\CmsSeoPlugin\Content\Check\H1Check;
use Softspring\CmsSeoPlugin\Content\Check\HeadingStructureCheck;
use Softspring\CmsSeoPlugin\Content\Check\ImageAltCheck;
use Softspring\CmsSeoPlugin\Content\Check\MetaDescriptionCheck;
use Softspring\CmsSeoPlugin\Content\Check\RobotsCheck;
use Softspring\CmsSeoPlugin\Content\Check\SeoCheckContext;
use Softspring\CmsSeoPlugin\Content\Check\TitleCheck;
use Softspring\CmsSeoPlugin\Content\Check\WordCountCheck;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisResult;
use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class BasicSeoAnalyzer
{
    public function __construct(
        private readonly ?ChecksLoader $checksLoader = null,
    ) {}

    public function analyze(PageUrl $pageUrl, PublishedPage $page): SeoAnalysisResult
    {
        if (null !== $page->error) {
            return $this->singleCheckResult(new SeoCheck('fetch_error', 'error', 0, ['message' => $page->error], [
                'error' => $page->error,
                'url' => $pageUrl->url,
            ]));
        }

        if (!$page->isSuccessful()) {
            return $this->singleCheckResult(new SeoCheck('http_status', 'error', 0, ['statusCode' => $page->statusCode], [
                'status_code' => (string) ($page->statusCode ?? '-'),
                'url' => $pageUrl->url,
            ]));
        }

        if (!$page->isHtml() || null === $page->html) {
            return $this->singleCheckResult(new SeoCheck('html_content_type', 'error', 0, ['contentType' => $page->contentType], [
                'content_type' => $page->contentType ?? '-',
                'url' => $pageUrl->url,
            ]));
        }

        $context = new SeoCheckContext($pageUrl, $page, $this->loadDocument($page->html));
        $checks = [];

        foreach ($this->checksLoader()->getChecks() as $check) {
            $checks[] = $check->check($context);
        }

        return new SeoAnalysisResult(
            $this->score($checks),
            $this->severity($checks),
            $checks,
            $context->metrics(),
        );
    }

    private function loadDocument(string $html): \DOMDocument
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, \LIBXML_NOWARNING | \LIBXML_NOERROR | \LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    private function checksLoader(): ChecksLoader
    {
        return $this->checksLoader ?? new ChecksLoader([
            new TitleCheck(),
            new MetaDescriptionCheck(),
            new CanonicalCheck(),
            new AlternatesCheck(),
            new H1Check(),
            new HeadingStructureCheck(),
            new ImageAltCheck(),
            new RobotsCheck(),
            new WordCountCheck(),
        ]);
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

    private function singleCheckResult(SeoCheck $check): SeoAnalysisResult
    {
        return new SeoAnalysisResult($check->score, $check->severity, [$check], []);
    }
}
