<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Seo;

final readonly class SeoAnalysisResult
{
    /**
     * @param SeoCheck[] $checks
     * @param array<string, string|int|float|bool|null> $metrics
     * @param string[] $sources
     */
    public function __construct(
        public int $score,
        public string $severity,
        public array $checks,
        public array $metrics,
        public array $sources = ['published_page'],
    ) {}
}
