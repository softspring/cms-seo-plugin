<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Seo;

interface SeoAnalysisDispatcherInterface
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(bool $sites, bool $contents, bool $async): array;
}
