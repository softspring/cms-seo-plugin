<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class WordCountCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'word_count';
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $wordCount = $context->wordCount($context->textContent('//body'));
        $details = [
            'word_count' => (string) $wordCount,
            'recommended' => '250+',
        ];

        if ($wordCount < 250) {
            return new SeoCheck('word_count', 'info', 70, ['wordCount' => $wordCount], $details);
        }

        return new SeoCheck('word_count', 'ok', 100, ['wordCount' => $wordCount], $details);
    }
}
