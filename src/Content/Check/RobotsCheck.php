<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class RobotsCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'robots';
    }

    public function getPriority(): int
    {
        return 200;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $robots = $context->attribute('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="robots"]', 'content');
        $details = [
            'value' => '' !== $robots ? $robots : '-',
        ];

        if ('' === $robots) {
            return new SeoCheck('robots', 'ok', 100, [], $details);
        }

        if (str_contains(strtolower($robots), 'noindex')) {
            return new SeoCheck('robots_noindex', 'error', 0, ['robots' => $robots], $details);
        }

        return new SeoCheck('robots', 'ok', 100, ['robots' => $robots], $details);
    }
}
