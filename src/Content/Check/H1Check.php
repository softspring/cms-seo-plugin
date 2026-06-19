<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class H1Check implements CheckInterface
{
    public function getCode(): string
    {
        return 'h1';
    }

    public function getPriority(): int
    {
        return 500;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $h1Texts = $context->textList('//h1');
        $count = \count($h1Texts);
        $details = [
            'count' => (string) $count,
            'values' => $context->limitedList($h1Texts),
        ];

        if (1 === $count) {
            return new SeoCheck('h1', 'ok', 100, ['count' => $count], $details);
        }

        return new SeoCheck(0 === $count ? 'h1_missing' : 'h1_multiple', 'error', 20, ['count' => $count], $details);
    }
}
