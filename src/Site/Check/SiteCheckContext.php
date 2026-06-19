<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsBundle\Model\SiteInterface;
use Softspring\CmsSeoPlugin\Site\PublicResource;
use Softspring\CmsSeoPlugin\Site\PublicResourceFetcher;

class SiteCheckContext
{
    /**
     * @var array<string, PublicResource>
     */
    private array $resources = [];

    public function __construct(
        public readonly SiteInterface $site,
        private readonly PublicResourceFetcher $fetcher,
    ) {
    }

    public function config(): array
    {
        return $this->site->getConfig() ?? [];
    }

    public function siteId(): string
    {
        return (string) $this->site->getId();
    }

    public function baseUrl(): ?string
    {
        $host = $this->canonicalHost();
        if (null === $host) {
            return null;
        }

        $scheme = $this->site->getCanonicalScheme() ?? (($this->config()['https_redirect'] ?? true) ? 'https' : 'http');
        $port = $this->site->getCanonicalPort();
        $portPart = null !== $port && !\in_array($port, [80, 443], true) ? ':'.$port : '';

        return $scheme.'://'.$host.$portPart;
    }

    public function absoluteUrl(string $path): ?string
    {
        $baseUrl = $this->baseUrl();
        if (null === $baseUrl) {
            return null;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    public function robotsUrl(): ?string
    {
        if (empty($this->config()['robots']['mode'])) {
            return null;
        }

        return $this->absoluteUrl('/robots.txt');
    }

    public function sitemapIndexUrl(): ?string
    {
        if (empty($this->config()['sitemaps_index']['enabled']) || empty($this->config()['sitemaps_index']['url'])) {
            return null;
        }

        return $this->absoluteUrl((string) $this->config()['sitemaps_index']['url']);
    }

    /**
     * @return string[]
     */
    public function configuredSitemapUrls(): array
    {
        $urls = [];

        foreach ($this->config()['sitemaps'] ?? [] as $sitemap) {
            if (!\is_array($sitemap) || empty($sitemap['url'])) {
                continue;
            }

            $url = $this->absoluteUrl((string) $sitemap['url']);
            if (null !== $url) {
                $urls[] = $url;
            }
        }

        foreach ($this->config()['sitemaps_index']['external_sitemaps'] ?? [] as $externalSitemapUrl) {
            if (\is_string($externalSitemapUrl) && '' !== $externalSitemapUrl) {
                $urls[] = $externalSitemapUrl;
            }
        }

        return array_values(array_unique($urls));
    }

    public function fetch(?string $url): ?PublicResource
    {
        if (null === $url) {
            return null;
        }

        return $this->resources[$url] ??= $this->fetcher->fetch($url);
    }

    public function fetchWithoutRedirects(?string $url): ?PublicResource
    {
        if (null === $url) {
            return null;
        }

        return $this->resources[$url.'#no_redirects'] ??= $this->fetcher->fetch($url, 0);
    }

    private function canonicalHost(): ?string
    {
        if (null !== $this->site->getCanonicalHost()) {
            return $this->site->getCanonicalHost();
        }

        $firstHost = null;

        foreach ($this->config()['hosts'] ?? [] as $hostConfig) {
            if (!\is_array($hostConfig) || empty($hostConfig['domain'])) {
                continue;
            }

            $domain = (string) $hostConfig['domain'];
            $firstHost ??= $domain;

            if (!empty($hostConfig['canonical'])) {
                return $domain;
            }
        }

        return $firstHost;
    }
}
