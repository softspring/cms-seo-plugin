<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class ImageAltCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'image_alt';
    }

    public function getPriority(): int
    {
        return 300;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $imageCount = \count($context->attributeList('//img', 'src'));
        $imagesWithoutAltSources = $context->attributeList('//img[not(@alt) or normalize-space(@alt)=""]', 'src');
        $missingAlt = \count($imagesWithoutAltSources);
        $details = [
            'image_count' => (string) $imageCount,
            'missing_alt' => (string) $missingAlt,
            'values' => $context->limitedList($imagesWithoutAltSources),
        ];

        if (0 === $imageCount) {
            return new SeoCheck('image_alt', 'info', 100, ['imageCount' => 0, 'missingAlt' => 0], $details);
        }

        if ($missingAlt > 0) {
            return new SeoCheck('image_alt', 'warning', 60, ['imageCount' => $imageCount, 'missingAlt' => $missingAlt], $details);
        }

        return new SeoCheck('image_alt', 'ok', 100, ['imageCount' => $imageCount, 'missingAlt' => 0], $details);
    }
}
