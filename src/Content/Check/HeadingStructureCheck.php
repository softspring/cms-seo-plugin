<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class HeadingStructureCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'heading_structure';
    }

    public function getPriority(): int
    {
        return 400;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $h2Texts = $context->textList('//h2');
        $count = \count($h2Texts);
        $details = [
            'h2_count' => (string) $count,
            'values' => $context->limitedList($h2Texts),
        ];

        if ($count > 0) {
            return new SeoCheck('heading_structure', 'ok', 100, ['h2Count' => $count], $details);
        }

        return new SeoCheck('heading_structure', 'info', 75, ['h2Count' => $count], $details);
    }
}
