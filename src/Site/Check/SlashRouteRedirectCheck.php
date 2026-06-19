<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class SlashRouteRedirectCheck implements SiteCheckInterface
{
    public function getCode(): string
    {
        return 'slash_route_redirect';
    }

    public function getPriority(): int
    {
        return 400;
    }

    public function check(SiteCheckContext $context): SeoCheck
    {
        $slashRoute = $context->config()['slash_route'] ?? [];
        if (!\is_array($slashRoute) || empty($slashRoute['enabled'])) {
            return new SeoCheck('slash_route_redirect', 'info', 100, [], [
                'url' => $context->absoluteUrl('/') ?? '-',
                'value' => 'slash route disabled',
            ]);
        }

        $url = $context->absoluteUrl('/');
        $expectedStatusCode = (int) ($slashRoute['redirect_code'] ?? 301);
        $expectedLocations = $this->expectedLocations($context);
        $resource = $context->fetchWithoutRedirects($url);

        if (null === $resource || null !== $resource->error) {
            return new SeoCheck('slash_route_redirect', 'error', 0, [], [
                'url' => $url ?? '-',
                'expected' => $expectedLocations,
                'error' => $resource?->error ?? 'Unknown error',
            ]);
        }

        $details = [
            'url' => $url ?? '-',
            'status_code' => (string) ($resource->statusCode ?? '-'),
            'expected' => $expectedLocations,
            'value' => $resource->location ?? '-',
        ];

        if ($resource->statusCode !== $expectedStatusCode) {
            return new SeoCheck('slash_route_redirect', 'warning', 50, [
                'expectedStatusCode' => $expectedStatusCode,
                'statusCode' => $resource->statusCode,
            ], $details);
        }

        if (null === $resource->location || !\in_array($resource->location, $expectedLocations, true)) {
            return new SeoCheck('slash_route_redirect', 'warning', 60, [
                'expectedStatusCode' => $expectedStatusCode,
                'statusCode' => $resource->statusCode,
            ], $details);
        }

        return new SeoCheck('slash_route_redirect', 'ok', 100, [
            'statusCode' => $resource->statusCode,
        ], $details);
    }

    /**
     * @return string[]
     */
    private function expectedLocations(SiteCheckContext $context): array
    {
        $locations = [];

        foreach ($context->config()['paths'] ?? [] as $pathConfig) {
            if (!\is_array($pathConfig) || empty($pathConfig['path'])) {
                continue;
            }

            $path = (string) $pathConfig['path'];
            if (!empty($pathConfig['trailing_slash_on_root'])) {
                $path = rtrim($path, '/').'/';
            }

            $url = $context->absoluteUrl($path);
            if (null !== $url) {
                $locations[] = $url;
            }
        }

        return [] !== $locations ? array_values(array_unique($locations)) : [$context->absoluteUrl('/') ?? '/'];
    }
}
