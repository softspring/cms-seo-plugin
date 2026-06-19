<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Tests\Unit\Content;

use PHPUnit\Framework\TestCase;
use Softspring\CmsBundle\Entity\Content;
use Softspring\CmsBundle\Entity\Route;
use Softspring\CmsBundle\Entity\RoutePath;
use Softspring\CmsBundle\Entity\Site;
use Softspring\CmsSeoPlugin\Content\BasicSeoAnalyzer;
use Softspring\CmsSeoPlugin\Content\Check\CheckInterface;
use Softspring\CmsSeoPlugin\Content\Check\ChecksLoader;
use Softspring\CmsSeoPlugin\Content\Check\SeoCheckContext;
use Softspring\CmsSeoPlugin\Content\PageUrl;
use Softspring\CmsSeoPlugin\Content\PublishedPage;
use Softspring\CmsSeoPlugin\Seo\SeoAnalysisResult;
use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class BasicSeoAnalyzerTest extends TestCase
{
    public function testAlternatesCheckPassesWhenAllSiteLocalesAreGenerated(): void
    {
        $result = $this->analyze($this->html([
            ['es', 'https://example.org/es/inicio'],
            ['en', 'https://example.org/en/home'],
        ]), ['es', 'en']);

        $check = $this->check($result, 'alternates');

        $this->assertSame('ok', $check->severity);
        $this->assertSame(100, $check->score);
        $this->assertSame(['expected' => 2, 'found' => 2, 'missing' => 0], $check->data);
        $this->assertSame(['es', 'en'], $check->details['expected']);
        $this->assertSame(['es', 'en'], $check->details['found']);
    }

    public function testAlternatesCheckWarnsWhenSiteLocaleIsMissing(): void
    {
        $result = $this->analyze($this->html([
            ['es', 'https://example.org/es/inicio'],
        ]), ['es', 'en']);

        $check = $this->check($result, 'alternates');

        $this->assertSame('warning', $check->severity);
        $this->assertSame(60, $check->score);
        $this->assertSame(['expected' => 2, 'found' => 1, 'missing' => 1, 'invalidHrefs' => 0], $check->data);
        $this->assertSame(['en'], $check->details['missing']);
    }

    public function testAlternatesCheckWarnsWhenHrefIsEmpty(): void
    {
        $result = $this->analyze($this->html([
            ['es', 'https://example.org/es/inicio'],
            ['en', ''],
        ]), ['es', 'en']);

        $check = $this->check($result, 'alternates');

        $this->assertSame('warning', $check->severity);
        $this->assertSame(['en'], $check->details['invalid_hrefs']);
    }

    public function testAlternatesCheckDoesNotRequireAlternatesForSingleLocaleSites(): void
    {
        $result = $this->analyze($this->html([]), ['es']);

        $check = $this->check($result, 'alternates');

        $this->assertSame('ok', $check->severity);
        $this->assertSame(['expected' => 0, 'found' => 0, 'missing' => 0], $check->data);
    }

    public function testAlternatesCheckDoesNotRequireAlternatesForSingleLocaleContents(): void
    {
        $result = $this->analyze($this->html([]), ['es', 'en'], ['es']);

        $check = $this->check($result, 'alternates');

        $this->assertSame('ok', $check->severity);
        $this->assertSame(['expected' => 0, 'found' => 0, 'missing' => 0], $check->data);
    }

    public function testAnalyzerRunsChecksProvidedByLoader(): void
    {
        $site = new Site();
        $site->setId('default');
        $site->setConfig(['locales' => ['es']]);
        $check = new class implements CheckInterface {
            public function getCode(): string
            {
                return 'custom_project_check';
            }

            public function getPriority(): int
            {
                return 1000;
            }

            public function check(SeoCheckContext $context): SeoCheck
            {
                return new SeoCheck('custom_project_check', 'ok', 100, [
                    'path' => $context->pageUrl->path,
                ]);
            }
        };

        $result = (new BasicSeoAnalyzer(new ChecksLoader([$check])))->analyze(
            new PageUrl($site, new RoutePath(), '/es/inicio', 'https://example.org/es/inicio', 'es'),
            new PublishedPage('https://example.org/es/inicio', 200, 'text/html; charset=utf-8', $this->html([])),
        );

        $this->assertCount(1, $result->checks);
        $this->assertSame('custom_project_check', $result->checks[0]->code);
        $this->assertSame(['path' => '/es/inicio'], $result->checks[0]->data);
    }

    /**
     * @param string[] $locales
     */
    private function analyze(string $html, array $locales, ?array $contentLocales = null): SeoAnalysisResult
    {
        $site = new Site();
        $site->setId('default');
        $site->setConfig(['locales' => $locales]);

        $routePath = new RoutePath();
        $routePath->setLocale('es');

        if (null !== $contentLocales) {
            $content = new class extends Content {
            };
            $content->setLocales($contentLocales);

            $route = new Route();
            $route->addPath($routePath);
            $content->addRoute($route);
        }

        return (new BasicSeoAnalyzer())->analyze(
            new PageUrl($site, $routePath, '/es/inicio', 'https://example.org/es/inicio', 'es'),
            new PublishedPage('https://example.org/es/inicio', 200, 'text/html; charset=utf-8', $html),
        );
    }

    /**
     * @param array<int, array{0: string, 1: string}> $alternates
     */
    private function html(array $alternates): string
    {
        $alternateHtml = '';
        foreach ($alternates as [$hreflang, $href]) {
            $alternateHtml .= sprintf('<link rel="alternate" hreflang="%s" href="%s">', htmlspecialchars($hreflang, \ENT_QUOTES), htmlspecialchars($href, \ENT_QUOTES));
        }

        return sprintf(<<<HTML
<!doctype html>
<html>
<head>
    <title>Example SEO title for testing</title>
    <meta name="description" content="This is a useful meta description with enough length to pass the deterministic SEO description check.">
    <link rel="canonical" href="https://example.org/es/inicio">
    %s
</head>
<body>
    <h1>Example heading</h1>
    <h2>Section heading</h2>
    <p>%s</p>
</body>
</html>
HTML, $alternateHtml, str_repeat('word ', 260));
    }

    private function check(SeoAnalysisResult $result, string $code): SeoCheck
    {
        foreach ($result->checks as $check) {
            if ($code === $check->code) {
                return $check;
            }
        }

        self::fail(sprintf('Check "%s" was not found.', $code));
    }
}
