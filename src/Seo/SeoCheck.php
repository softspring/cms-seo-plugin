<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Seo;

final readonly class SeoCheck
{
    /**
     * @param array<string, string|int|float|bool|null> $data
     * @param array<string, string|string[]> $details
     */
    public function __construct(
        public string $code,
        public string $severity,
        public int $score,
        public array $data = [],
        public array $details = [],
    ) {}
}
