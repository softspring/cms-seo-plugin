<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PublicResourceFetcher
{
    public function __construct(
        protected HttpClientInterface $httpClient,
    ) {
    }

    public function fetch(string $publicUrl, int $maxRedirects = 5): PublicResource
    {
        try {
            [$url, $headers] = $this->buildRequest($publicUrl);

            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
                'max_redirects' => $maxRedirects,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? null;
            $location = $headers['location'][0] ?? null;

            return new PublicResource($publicUrl, $statusCode, $contentType, $response->getContent(false), $location);
        } catch (\Throwable $exception) {
            return new PublicResource($publicUrl, null, null, null, null, $exception->getMessage());
        }
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildRequest(string $publicUrl): array
    {
        $headers = [
            'User-Agent' => 'Softspring CMS SEO Plugin',
        ];
        $cacheBustedPublicUrl = $this->addCacheBuster($publicUrl);

        $host = parse_url($cacheBustedPublicUrl, \PHP_URL_HOST);
        if (!\is_string($host) || !str_ends_with($host, '.local')) {
            return [$cacheBustedPublicUrl, $headers];
        }

        $path = parse_url($cacheBustedPublicUrl, \PHP_URL_PATH);
        $query = parse_url($cacheBustedPublicUrl, \PHP_URL_QUERY);
        $pathAndQuery = (\is_string($path) && '' !== $path ? $path : '/').(\is_string($query) && '' !== $query ? '?'.$query : '');

        return [
            'http://balancer:8008'.$pathAndQuery,
            $headers + [
                'Host' => $host,
                'X-Forwarded-Host' => $host,
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Port' => '443',
            ],
        ];
    }

    private function addCacheBuster(string $url): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'sfs_cms_seo_bust='.bin2hex(random_bytes(8));
    }
}
