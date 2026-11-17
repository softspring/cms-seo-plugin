<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Tests\Unit\Content;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Softspring\CmsSeoPlugin\Content\WordStatistics\HtmlWordStatistics;
use Softspring\CmsSeoPlugin\Content\WordStatistics\StopWords;

class HtmlWordStatisticsTest extends TestCase
{
    public function testFrequencyDensityAndSpanishStopWords(): void
    {
        $result = new HtmlWordStatistics()->analyze('<h2>Symfony y CMS</h2><p>Symfony es un CMS. CMS <strong>rápido</strong>.</p>', 'es');
        self::assertSame(['wordCount' => 9, 'analyzedWordCount' => 6, 'uniqueWordCount' => 3, 'highlightedWordCount' => 3], $result['summary']);
        self::assertSame(['cms', 'symfony', 'rápido'], array_column($result['frequentTerms'], 'word'));
        self::assertSame(3, $result['frequentTerms'][0]['count']);
        self::assertSame(33.33, $result['frequentTerms'][0]['density']);
        self::assertSame(1, $result['frequentTerms'][0]['contexts']['h2']);
    }

    public function testNestedFormattingUsesTheHighestWeightOncePerOccurrence(): void
    {
        $result = new HtmlWordStatistics()->analyze('<h1><strong>Symfony</strong></h1><p><strong><b>Symfony</b></strong></p>', 'en');
        $term = $result['frequentTerms'][0];
        self::assertSame(2, $term['count']);
        self::assertSame(2, $term['highlightedCount']);
        self::assertSame(9, $term['weightedCount']);
        self::assertSame(7, $term['highlightScore']);
        self::assertSame(1, $term['contexts']['h1']);
        self::assertSame(2, $term['contexts']['strong']);
    }

    public function testProminenceAndFrequencyHaveSeparateRankings(): void
    {
        $result = new HtmlWordStatistics()->analyze('<p>' . str_repeat('simple ', 20) . '</p><h2>Arquitectura</h2>', 'es');
        self::assertSame('simple', $result['frequentTerms'][0]['word']);
        self::assertSame('arquitectura', $result['prominentTerms'][0]['word']);
        self::assertCount(1, $result['prominentTerms']);
    }

    public function testInlineWordFragmentsAndBlockBoundariesArePreserved(): void
    {
        $result = new HtmlWordStatistics()->analyze('<p>Sym<strong>fony</strong></p><p>CMS<br>CMS</p>', 'en');
        self::assertSame(3, $result['summary']['wordCount']);
        self::assertSame(['cms', 'symfony'], array_column($result['frequentTerms'], 'word'));
        self::assertSame('symfony', $result['prominentTerms'][0]['word']);
    }

    public function testEntitiesAccentsAndHiddenOrNonContentText(): void
    {
        $html = '<script>tracking</script><style>tracking</style><nav>navigation</nav><footer>footer</footer>'
            . '<p hidden>hidden</p><p aria-hidden="true">hidden</p><p style="display: none">hidden</p>'
            . '<p title="attribute">Tecnolog&iacute;a &amp; tecnología 2026</p><template>template</template>';
        $result = new HtmlWordStatistics()->analyze($html, 'es');
        self::assertSame(2, $result['summary']['wordCount']);
        self::assertCount(1, $result['frequentTerms']);
        self::assertSame('tecnología', $result['frequentTerms'][0]['word']);
        self::assertSame(2, $result['frequentTerms'][0]['count']);
    }

    public function testEnglishRegionalStopWordsCustomWordsAndWeights(): void
    {
        $analyzer = new HtmlWordStatistics(new StopWords(), ['stop_words' => ['en' => ['example']], 'weights' => ['mark' => 7]]);
        $result = $analyzer->analyze('<p>The example CMS and <mark>platform</mark></p>', 'en-GB');
        self::assertSame(['cms', 'platform'], array_column($result['frequentTerms'], 'word'));
        self::assertSame(6, $result['prominentTerms'][0]['highlightScore']);
        self::assertTrue($result['parameters']['builtInStopWords']);
    }

    public function testEmptyContentUnknownLanguageAndLimitDoNotInventResults(): void
    {
        $analyzer = new HtmlWordStatistics();
        $empty = $analyzer->analyze('', 'fr');
        self::assertSame(0, $empty['summary']['wordCount']);
        self::assertSame([], $empty['frequentTerms']);
        self::assertSame([], $empty['prominentTerms']);
        self::assertFalse($empty['parameters']['builtInStopWords']);
        $limited = $analyzer->analyze('<p>Gamma beta alpha</p>', 'en', 2);
        self::assertSame(['alpha', 'beta'], array_column($limited['frequentTerms'], 'word'));
        self::assertSame(3, $limited['summary']['uniqueWordCount']);
    }

    public function testInvalidLimitsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HtmlWordStatistics()->analyze('<p>Example</p>', 'en', 101);
    }
}
