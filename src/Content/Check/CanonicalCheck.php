<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class CanonicalCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'canonical';
    }

    public function getPriority(): int
    {
        return 700;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $canonical = $context->attribute('//link[translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="canonical"]', 'href');
        $details = [
            'value' => '' !== $canonical ? $canonical : '-',
            'expected_path' => $context->normalizedUrlPath($context->pageUrl->url),
            'actual_path' => '' !== $canonical ? $context->normalizedUrlPath($canonical) : '-',
        ];

        if ('' === $canonical) {
            return new SeoCheck('canonical', 'warning', 50, [], $details);
        }

        $canonicalPath = $context->normalizedUrlPath($canonical);
        $currentPath = $context->normalizedUrlPath($context->pageUrl->url);
        if ($canonicalPath !== $currentPath) {
            return new SeoCheck('canonical', 'warning', 60, ['canonical' => $canonical], $details);
        }

        return new SeoCheck('canonical', 'ok', 100, ['canonical' => $canonical], $details);
    }
}
