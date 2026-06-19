<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Tests\Unit\Site;

use PHPUnit\Framework\TestCase;
use Softspring\CmsBundle\Entity\Site;
use Softspring\CmsSeoPlugin\Site\PublicResourceFetcher;
use Softspring\CmsSeoPlugin\Site\Check\RobotsReachableCheck;
use Softspring\CmsSeoPlugin\Site\Check\RobotsSitemapIndexCheck;
use Softspring\CmsSeoPlugin\Site\Check\SitemapIndexContainsConfiguredSitemapsCheck;
use Softspring\CmsSeoPlugin\Site\Check\SitemapIndexReachableCheck;
use Softspring\CmsSeoPlugin\Site\Check\SitemapReachableCheck;
use Softspring\CmsSeoPlugin\Site\Check\SlashRouteRedirectCheck;
use Softspring\CmsSeoPlugin\Site\Check\SiteChecksLoader;
use Softspring\CmsSeoPlugin\Site\SiteAnalyzer;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SiteAnalyzerTest extends TestCase
{
    public function testSiteAnalysisPassesWhenRobotsAndSitemapsMatchConfiguration(): void
    {
        $client = new MockHttpClient([
            new MockResponse("User-agent: *\nAllow: /\n\nSitemap: https://example.org/sitemap_index.xml", ['response_headers' => ['content-type' => 'text/plain']]),
            new MockResponse($this->sitemapIndexXml([
                'https://example.org/sitemap.xml',
                'https://example.org/sitemap_blog.xml',
            ]), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse('', ['http_code' => 301, 'response_headers' => ['location' => 'https://example.org/es/']]),
        ]);

        $analysis = $this->analyzer($client)->analyze($this->site());

        $this->assertSame('ok', $analysis->severity);
        $this->assertSame(100, $analysis->score);
        $this->assertSame(6, $analysis->metrics['checks']);
    }

    public function testSiteAnalysisWarnsWhenRobotsDoesNotExposeSitemapIndex(): void
    {
        $client = new MockHttpClient([
            new MockResponse("User-agent: *\nAllow: /\n\nSitemap: https://example.org/sitemap.xml", ['response_headers' => ['content-type' => 'text/plain']]),
            new MockResponse($this->sitemapIndexXml([
                'https://example.org/sitemap.xml',
                'https://example.org/sitemap_blog.xml',
            ]), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse('', ['http_code' => 301, 'response_headers' => ['location' => 'https://example.org/es/']]),
        ]);

        $analysis = $this->analyzer($client)->analyze($this->site());
        $check = $this->check($analysis->checks, 'robots_sitemap_index');

        $this->assertSame('warning', $analysis->severity);
        $this->assertSame('warning', $check->severity);
        $this->assertSame('https://example.org/sitemap_index.xml', $check->details['expected']);
    }

    public function testDisabledSiteCheckIsSkipped(): void
    {
        $client = new MockHttpClient([
            new MockResponse("User-agent: *\nAllow: /", ['response_headers' => ['content-type' => 'text/plain']]),
            new MockResponse($this->sitemapIndexXml(['https://example.org/sitemap.xml', 'https://example.org/sitemap_blog.xml']), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse('', ['http_code' => 301, 'response_headers' => ['location' => 'https://example.org/es/']]),
        ]);

        $analysis = $this->analyzer($client, [
            'enabled' => true,
            'checks' => [
                'robots_sitemap_index' => false,
            ],
        ])->analyze($this->site());

        $this->assertNull($this->findCheck($analysis->checks, 'robots_sitemap_index'));
    }

    public function testSiteAnalysisWarnsWhenSlashRouteRedirectLocationIsUnexpected(): void
    {
        $client = new MockHttpClient([
            new MockResponse("User-agent: *\nAllow: /\n\nSitemap: https://example.org/sitemap_index.xml", ['response_headers' => ['content-type' => 'text/plain']]),
            new MockResponse($this->sitemapIndexXml([
                'https://example.org/sitemap.xml',
                'https://example.org/sitemap_blog.xml',
            ]), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse($this->sitemapXml(), ['response_headers' => ['content-type' => 'application/xml']]),
            new MockResponse('', ['http_code' => 301, 'response_headers' => ['location' => 'https://example.org/unexpected/']]),
        ]);

        $analysis = $this->analyzer($client)->analyze($this->site());
        $check = $this->check($analysis->checks, 'slash_route_redirect');

        $this->assertSame('warning', $analysis->severity);
        $this->assertSame('warning', $check->severity);
        $this->assertSame(['https://example.org/es/', 'https://example.org/en/'], $check->details['expected']);
        $this->assertSame('https://example.org/unexpected/', $check->details['value']);
    }

    private function analyzer(MockHttpClient $client, array $config = ['enabled' => true, 'checks' => []]): SiteAnalyzer
    {
        return new SiteAnalyzer(
            new SiteChecksLoader([
                new RobotsReachableCheck(),
                new RobotsSitemapIndexCheck(),
                new SitemapIndexReachableCheck(),
                new SitemapIndexContainsConfiguredSitemapsCheck(),
                new SitemapReachableCheck(),
                new SlashRouteRedirectCheck(),
            ]),
            new PublicResourceFetcher($client),
            $config,
        );
    }

    private function site(): Site
    {
        $site = new Site();
        $site->setId('default');
        $site->setConfig([
            'https_redirect' => true,
            'robots' => ['mode' => 'static'],
            'hosts' => [
                ['domain' => 'example.org', 'scheme' => 'https', 'canonical' => true],
            ],
            'paths' => [
                ['path' => '/es/', 'locale' => 'es', 'trailing_slash_on_root' => true],
                ['path' => '/en/', 'locale' => 'en', 'trailing_slash_on_root' => true],
            ],
            'slash_route' => [
                'enabled' => true,
                'behaviour' => 'redirect_to_route_with_user_language',
                'route' => 'home',
                'redirect_code' => 301,
            ],
            'sitemaps' => [
                'default' => ['url' => 'sitemap.xml'],
            ],
            'sitemaps_index' => [
                'enabled' => true,
                'url' => 'sitemap_index.xml',
                'external_sitemaps' => ['https://example.org/sitemap_blog.xml'],
            ],
        ]);

        return $site;
    }

    /**
     * @param string[] $urls
     */
    private function sitemapIndexXml(array $urls): string
    {
        $entries = '';
        foreach ($urls as $url) {
            $entries .= sprintf('<sitemap><loc>%s</loc></sitemap>', htmlspecialchars($url, \ENT_XML1));
        }

        return sprintf('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">%s</sitemapindex>', $entries);
    }

    private function sitemapXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.org/</loc></url></urlset>';
    }

    /**
     * @param array<int, mixed> $checks
     */
    private function check(array $checks, string $code): mixed
    {
        $check = $this->findCheck($checks, $code);
        if (null === $check) {
            self::fail(sprintf('Check "%s" was not found.', $code));
        }

        return $check;
    }

    /**
     * @param array<int, mixed> $checks
     */
    private function findCheck(array $checks, string $code): mixed
    {
        foreach ($checks as $check) {
            if ($code === $check->code) {
                return $check;
            }
        }

        return null;
    }
}
