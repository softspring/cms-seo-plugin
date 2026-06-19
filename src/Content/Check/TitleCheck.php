<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class TitleCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'title';
    }

    public function getPriority(): int
    {
        return 900;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $title = $context->textContent('//title');
        $length = mb_strlen($title);
        $details = [
            'value' => '' !== $title ? $title : '-',
            'length' => (string) $length,
            'recommended' => '30-65',
        ];

        if (0 === $length) {
            return new SeoCheck('title', 'error', 0, ['length' => $length], $details);
        }

        if ($length < 30 || $length > 65) {
            return new SeoCheck('title', 'warning', 60, ['length' => $length], $details);
        }

        return new SeoCheck('title', 'ok', 100, ['length' => $length], $details);
    }
}
