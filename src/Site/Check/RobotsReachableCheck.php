<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class RobotsReachableCheck implements SiteCheckInterface
{
    public function getCode(): string
    {
        return 'robots_reachable';
    }

    public function getPriority(): int
    {
        return 900;
    }

    public function check(SiteCheckContext $context): SeoCheck
    {
        $url = $context->robotsUrl();
        if (null === $url) {
            return new SeoCheck('robots_reachable', 'info', 100, [], [
                'url' => '-',
                'value' => 'robots disabled',
            ]);
        }

        $resource = $context->fetch($url);
        if (null === $resource || null !== $resource->error) {
            return new SeoCheck('robots_reachable', 'error', 0, [], [
                'url' => $url,
                'error' => $resource?->error ?? 'Unknown error',
            ]);
        }

        $details = [
            'url' => $url,
            'status_code' => (string) ($resource->statusCode ?? '-'),
            'content_type' => $resource->contentType ?? '-',
        ];

        if (!$resource->isSuccessful()) {
            return new SeoCheck('robots_reachable', 'error', 0, ['statusCode' => $resource->statusCode], $details);
        }

        if (!$resource->isPlainText()) {
            return new SeoCheck('robots_reachable', 'warning', 70, ['contentType' => $resource->contentType], $details);
        }

        return new SeoCheck('robots_reachable', 'ok', 100, ['statusCode' => $resource->statusCode], $details);
    }
}
