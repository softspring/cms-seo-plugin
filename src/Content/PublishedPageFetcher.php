<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PublishedPageFetcher
{
    public function __construct(
        protected HttpClientInterface $httpClient,
    ) {}

    public function fetch(PageUrl $pageUrl): PublishedPage
    {
        try {
            [$url, $headers] = $this->buildRequest($pageUrl->url);

            $response = $this->httpClient->request('GET', $url, [
                'headers' => $headers,
                'max_redirects' => 5,
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();
            $headers = $response->getHeaders(false);
            $contentType = $headers['content-type'][0] ?? null;
            $html = $statusCode >= 200 && $statusCode < 300 ? $response->getContent(false) : null;

            return new PublishedPage($pageUrl->url, $statusCode, $contentType, $html);
        } catch (\Throwable $exception) {
            return new PublishedPage($pageUrl->url, null, null, null, $exception->getMessage());
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
        $pathAndQuery = (\is_string($path) && '' !== $path ? $path : '/') . (\is_string($query) && '' !== $query ? '?' . $query : '');

        return [
            'http://balancer:8008' . $pathAndQuery,
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

        return $url . $separator . 'sfs_cms_seo_bust=' . bin2hex(random_bytes(8));
    }
}
