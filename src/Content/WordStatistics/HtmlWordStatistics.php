<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\WordStatistics;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use InvalidArgumentException;

class HtmlWordStatistics
{
    public const WEIGHTS = ['h1' => 6, 'h2' => 5, 'h3' => 4, 'h4' => 3, 'h5' => 2, 'h6' => 2,
        'strong' => 3, 'em' => 2, 'mark' => 3, 'u' => 2];

    private const SKIP = ['head', 'script', 'style', 'noscript', 'template', 'svg', 'canvas', 'nav', 'footer', 'form'];
    private const BLOCKS = ['address', 'article', 'aside', 'blockquote', 'br', 'dd', 'div', 'dl', 'dt', 'figcaption',
        'figure', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hr', 'li', 'main', 'ol', 'p', 'pre', 'section', 'table', 'td', 'th', 'tr', 'ul'];

    public function __construct(private readonly StopWords $stopWords = new StopWords(), private readonly array $config = []) {}

    public function analyze(string $html, string $locale, ?int $limit = null): array
    {
        $limit ??= $this->config['limit'] ?? 20;
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('The term limit must be between 1 and 100.');
        }
        $weights = array_replace(self::WEIGHTS, $this->config['weights'] ?? []);
        $minimum = $this->config['min_word_length'] ?? 2;
        $stopWords = $this->stopWords->forLocale($locale, $this->config['stop_words'] ?? []);
        $document = new DOMDocument();
        $document->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $text = '';
        $segments = [];
        $this->collect($document->getElementsByTagName('body')->item(0), [], $text, $segments);
        preg_match_all('/[\p{L}\p{N}][\p{L}\p{M}\p{N}]*(?:[\x{2019}\x{0027}-][\p{L}\p{N}][\p{L}\p{M}\p{N}]*)*/u', $text, $matches, PREG_OFFSET_CAPTURE);
        $terms = [];
        $wordCount = $analyzedCount = $highlightedCount = $cursor = 0;
        foreach ($matches[0] as [$token, $offset]) {
            if (!preg_match('/\p{L}/u', $token)) {
                continue;
            }
            ++$wordCount;
            $word = mb_strtolower($token, 'UTF-8');
            if (class_exists(\Normalizer::class)) {
                $word = \Normalizer::normalize($word, \Normalizer::FORM_C) ?: $word;
            }
            if (mb_strlen($word, 'UTF-8') < $minimum || isset($stopWords[$word])) {
                continue;
            }
            ++$analyzedCount;
            $end = $offset + strlen($token);
            while (isset($segments[$cursor]) && $segments[$cursor]['end'] <= $offset) {
                ++$cursor;
            }
            $contexts = [];
            for ($index = $cursor; isset($segments[$index]) && $segments[$index]['start'] < $end; ++$index) {
                $contexts += $segments[$index]['contexts'];
            }
            $weight = 1;
            foreach ($contexts as $context => $_) {
                $weight = max($weight, $weights[$context] ?? 1);
            }
            $terms[$word] ??= ['word' => $word, 'count' => 0, 'density' => 0.0, 'weightedCount' => 0,
                'highlightScore' => 0, 'highlightedCount' => 0, 'contexts' => array_fill_keys(array_keys(self::WEIGHTS), 0)];
            ++$terms[$word]['count'];
            $terms[$word]['weightedCount'] += $weight;
            $terms[$word]['highlightScore'] += $weight - 1;
            if ([] !== $contexts) {
                ++$terms[$word]['highlightedCount'];
                ++$highlightedCount;
            }
            foreach ($contexts as $context => $_) {
                ++$terms[$word]['contexts'][$context];
            }
        }
        foreach ($terms as &$term) {
            $term['density'] = 0 === $wordCount ? 0.0 : round(100 * $term['count'] / $wordCount, 2);
        }
        unset($term);
        $frequent = array_values($terms);
        usort($frequent, static fn(array $a, array $b): int => $b['count'] <=> $a['count'] ?: strcmp($a['word'], $b['word']));
        $prominent = array_values(array_filter($terms, static fn(array $term): bool => $term['highlightedCount'] > 0));
        usort($prominent, static fn(array $a, array $b): int => $b['highlightScore'] <=> $a['highlightScore'] ?: $b['highlightedCount'] <=> $a['highlightedCount'] ?: strcmp($a['word'], $b['word']));

        return ['summary' => ['wordCount' => $wordCount, 'analyzedWordCount' => $analyzedCount,
            'uniqueWordCount' => count($terms), 'highlightedWordCount' => $highlightedCount],
            'frequentTerms' => array_slice($frequent, 0, $limit), 'prominentTerms' => array_slice($prominent, 0, $limit),
            'parameters' => ['limit' => $limit, 'minWordLength' => $minimum, 'weights' => $weights,
                'builtInStopWords' => $this->stopWords->supports($locale), 'densityDenominator' => 'wordCount']];
    }

    private function collect(?DOMNode $node, array $contexts, string &$text, array &$segments): void
    {
        if ($node instanceof DOMText) {
            $start = strlen($text);
            $text .= $node->textContent;
            $segments[] = ['start' => $start, 'end' => strlen($text), 'contexts' => $contexts];

            return;
        }
        if (!$node instanceof DOMElement) {
            return;
        }
        $tag = strtolower($node->tagName);
        if (in_array($tag, self::SKIP, true) || $node->hasAttribute('hidden')
            || 'true' === strtolower($node->getAttribute('aria-hidden'))
            || preg_match('/(?:display\s*:\s*none|visibility\s*:\s*hidden)/i', $node->getAttribute('style'))) {
            $text .= "\n";

            return;
        }
        $context = match ($tag) {
            'b' => 'strong', 'i' => 'em', default => $tag
        };
        if (isset(self::WEIGHTS[$context])) {
            $contexts[$context] = true;
        }
        $block = in_array($tag, self::BLOCKS, true);
        if ($block) {
            $text .= "\n";
        }
        foreach ($node->childNodes as $child) {
            $this->collect($child, $contexts, $text, $segments);
        }
        if ($block) {
            $text .= "\n";
        }
    }
}
