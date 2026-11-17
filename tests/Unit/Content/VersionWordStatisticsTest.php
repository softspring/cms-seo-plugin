<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Tests\Unit\Content;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsBundle\Model\SiteInterface;
use Softspring\CmsBundle\Render\ContentVersionRenderer;
use Softspring\CmsBundle\Render\Error\RenderErrorList;
use Softspring\CmsSeoPlugin\Content\WordStatistics\HtmlWordStatistics;
use Softspring\CmsSeoPlugin\Content\WordStatistics\VersionWordStatistics;

class VersionWordStatisticsTest extends TestCase
{
    private function version(): ContentVersionInterface
    {
        $site = $this->createMock(SiteInterface::class);
        $site->method('getId')->willReturn('main');
        $site->method('isEnabled')->willReturn(true);
        $site->method('getConfig')->willReturn(['locales' => ['es', 'en']]);
        $site->method('getCanonicalScheme')->willReturn('https');
        $site->method('getCanonicalHost')->willReturn('example.test');
        $content = $this->createMock(ContentInterface::class);
        $content->method('getId')->willReturn('content-a');
        $content->method('getLocales')->willReturn(['es', 'en']);
        $content->method('getDefaultLocale')->willReturn('es');
        $content->method('getSites')->willReturn(new ArrayCollection([$site]));
        $version = $this->createMock(ContentVersionInterface::class);
        $version->method('getId')->willReturn('version-a');
        $version->method('getVersionNumber')->willReturn(3);
        $version->method('getContent')->willReturn($content);

        return $version;
    }

    public function testRenderingErrorsAreNotReportedAsValidEmptyStatistics(): void
    {
        $renderer = $this->createMock(ContentVersionRenderer::class);
        $renderer->method('renderContainers')->willReturnCallback(static function ($version, $request, RenderErrorList $errors): array {
            $errors->add('test-module', new RuntimeException('Internal renderer details'), []);

            return ['main' => 'Partial output'];
        });
        $result = new VersionWordStatistics($renderer, new HtmlWordStatistics())->analyze($this->version());
        self::assertFalse($result['ok']);
        self::assertArrayNotHasKey('summary', $result);
        self::assertStringNotContainsString('Internal renderer details', json_encode($result));
    }

    public function testRendererExceptionsAreReportedWithoutLeakingTheirDetails(): void
    {
        $renderer = $this->createMock(ContentVersionRenderer::class);
        $renderer->method('renderContainers')->willThrowException(new RuntimeException('Private renderer detail'));
        $result = new VersionWordStatistics($renderer, new HtmlWordStatistics())->analyze($this->version());
        self::assertFalse($result['ok']);
        self::assertStringNotContainsString('Private renderer detail', json_encode($result));
    }

    public function testInvalidLocaleIsRejectedBeforeRendering(): void
    {
        $renderer = $this->createMock(ContentVersionRenderer::class);
        $renderer->expects(self::never())->method('renderContainers');
        $this->expectException(InvalidArgumentException::class);
        new VersionWordStatistics($renderer, new HtmlWordStatistics())->analyze($this->version(), 'fr');
    }

    public function testSiteMustBeAssignedToTheContent(): void
    {
        $renderer = $this->createMock(ContentVersionRenderer::class);
        $renderer->expects(self::never())->method('renderContainers');
        $this->expectException(InvalidArgumentException::class);
        new VersionWordStatistics($renderer, new HtmlWordStatistics())->analyze($this->version(), 'es', 'unrelated');
    }

    public function testVersionWithoutParentIsRejectedBeforeRendering(): void
    {
        $renderer = $this->createMock(ContentVersionRenderer::class);
        $renderer->expects(self::never())->method('renderContainers');
        $this->expectException(InvalidArgumentException::class);
        new VersionWordStatistics($renderer, new HtmlWordStatistics())->analyze($this->createMock(ContentVersionInterface::class));
    }
}
