<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Content\PageUrl;
use Softspring\CmsSeoPlugin\Content\PublishedPage;

class SeoCheckContext
{
    private const MAX_DETAIL_ITEMS = 10;

    public readonly \DOMXPath $xpath;

    public function __construct(
        public readonly PageUrl $pageUrl,
        public readonly PublishedPage $page,
        public readonly \DOMDocument $document,
    ) {
        $this->xpath = new \DOMXPath($document);
    }

    /**
     * @return array<string, int>
     */
    public function metrics(): array
    {
        $alternates = $this->alternates();
        $h1Texts = $this->textList('//h1');
        $h2Texts = $this->textList('//h2');
        $imageSources = $this->attributeList('//img', 'src');
        $imagesWithoutAltSources = $this->attributeList('//img[not(@alt) or normalize-space(@alt)=""]', 'src');

        return [
            'titleLength' => mb_strlen($this->textContent('//title')),
            'descriptionLength' => mb_strlen($this->attribute('//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="description"]', 'content')),
            'h1Count' => \count($h1Texts),
            'h2Count' => \count($h2Texts),
            'imageCount' => \count($imageSources),
            'imagesWithoutAlt' => \count($imagesWithoutAltSources),
            'alternateCount' => \count($alternates),
            'missingAlternateCount' => \count($this->missingAlternateLocales($alternates)),
            'wordCount' => $this->wordCount($this->textContent('//body')),
        ];
    }

    public function textContent(string $query): string
    {
        $nodes = $this->xpath->query($query);
        if (!$nodes instanceof \DOMNodeList || 0 === $nodes->length) {
            return '';
        }

        return $this->normalizeText($nodes->item(0)->textContent);
    }

    /**
     * @return string[]
     */
    public function textList(string $query): array
    {
        $nodes = $this->xpath->query($query);
        if (!$nodes instanceof \DOMNodeList) {
            return [];
        }

        $values = [];
        foreach ($nodes as $node) {
            $text = $this->normalizeText($node->textContent ?? '');
            $values[] = '' !== $text ? $text : '-';
        }

        return $values;
    }

    public function attribute(string $query, string $attribute): string
    {
        $nodes = $this->xpath->query($query);
        if (!$nodes instanceof \DOMNodeList || 0 === $nodes->length) {
            return '';
        }

        $node = $nodes->item(0);
        if (!$node instanceof \DOMElement) {
            return '';
        }

        return trim($node->getAttribute($attribute));
    }

    /**
     * @return string[]
     */
    public function attributeList(string $query, string $attribute): array
    {
        $nodes = $this->xpath->query($query);
        if (!$nodes instanceof \DOMNodeList) {
            return [];
        }

        $values = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $value = trim($node->getAttribute($attribute));
            $values[] = '' !== $value ? $value : '-';
        }

        return $values;
    }

    /**
     * @return array<int, array{hreflang: string, href: string}>
     */
    public function alternates(): array
    {
        $nodes = $this->xpath->query('//link[contains(concat(" ", normalize-space(translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")), " "), " alternate ")]');
        if (!$nodes instanceof \DOMNodeList) {
            return [];
        }

        $alternates = [];
        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $alternates[] = [
                'hreflang' => trim($node->getAttribute('hreflang')),
                'href' => trim($node->getAttribute('href')),
            ];
        }

        return $alternates;
    }

    /**
     * @return string[]
     */
    public function expectedAlternateLocales(): array
    {
        $siteLocales = $this->normalizedLocales($this->pageUrl->site->getConfig()['locales'] ?? []);
        $content = $this->pageUrl->routePath->getRoute()?->getContent();

        if (null !== $content) {
            $contentLocales = $this->normalizedLocales($content->getLocales() ?? []);
            $locales = [] !== $siteLocales ? array_values(array_intersect($contentLocales, $siteLocales)) : $contentLocales;
        } else {
            $locales = $siteLocales;
        }

        return \count($locales) > 1 ? $locales : [];
    }

    /**
     * @param array<int, array{hreflang: string, href: string}> $alternates
     * @return string[]
     */
    public function foundAlternateLocales(array $alternates): array
    {
        $locales = [];

        foreach ($alternates as $alternate) {
            if ('' === $alternate['hreflang']) {
                continue;
            }

            $locales[] = strtolower($alternate['hreflang']);
        }

        return array_values(array_unique($locales));
    }

    /**
     * @param array<int, array{hreflang: string, href: string}> $alternates
     * @return string[]
     */
    public function missingAlternateLocales(array $alternates): array
    {
        $foundLocales = $this->foundAlternateLocales($alternates);

        return array_values(array_filter($this->expectedAlternateLocales(), static fn(string $locale): bool => !in_array(strtolower($locale), $foundLocales, true)));
    }

    /**
     * @param array<int, array{hreflang: string, href: string}> $alternates
     * @return string[]
     */
    public function invalidAlternateHrefs(array $alternates): array
    {
        $invalidHrefs = [];

        foreach ($alternates as $alternate) {
            if ('' === $alternate['href']) {
                $invalidHrefs[] = '' !== $alternate['hreflang'] ? $alternate['hreflang'] : '-';
            }
        }

        return $invalidHrefs;
    }

    public function wordCount(string $text): int
    {
        if ('' === $text) {
            return 0;
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return \count($matches[0]);
    }

    /**
     * @param string[] $values
     * @return string[]
     */
    public function limitedList(array $values): array
    {
        if ([] === $values) {
            return [];
        }

        $limited = array_slice($values, 0, self::MAX_DETAIL_ITEMS);
        if (\count($values) > self::MAX_DETAIL_ITEMS) {
            $limited[] = sprintf('+%d', \count($values) - self::MAX_DETAIL_ITEMS);
        }

        return $limited;
    }

    public function normalizedUrlPath(string $url): string
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!\is_string($path) || '' === $path) {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    /**
     * @param mixed[] $locales
     * @return string[]
     */
    private function normalizedLocales(array $locales): array
    {
        $locales = array_map(static fn(mixed $locale): string => strtolower(trim((string) $locale)), $locales);
        $locales = array_values(array_filter($locales, static fn(string $locale): bool => '' !== $locale));

        return array_values(array_unique($locales));
    }
}
