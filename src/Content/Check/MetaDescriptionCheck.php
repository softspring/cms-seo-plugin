<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class MetaDescriptionCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'meta_description';
    }

    public function getPriority(): int
    {
        return 800;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $description = $context->attribute('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="description"]', 'content');
        $length = mb_strlen($description);
        $details = [
            'value' => '' !== $description ? $description : '-',
            'length' => (string) $length,
            'recommended' => '70-160',
        ];

        if (0 === $length) {
            return new SeoCheck('meta_description', 'error', 0, ['length' => $length], $details);
        }

        if ($length < 70 || $length > 160) {
            return new SeoCheck('meta_description', 'warning', 60, ['length' => $length], $details);
        }

        return new SeoCheck('meta_description', 'ok', 100, ['length' => $length], $details);
    }
}
