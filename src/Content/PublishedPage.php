<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content;

final readonly class PublishedPage
{
    public function __construct(
        public string $url,
        public ?int $statusCode,
        public ?string $contentType,
        public ?string $html,
        public ?string $error = null,
    ) {
    }

    public function isHtml(): bool
    {
        return null !== $this->contentType && str_contains(strtolower($this->contentType), 'text/html');
    }

    public function isSuccessful(): bool
    {
        return null !== $this->statusCode && $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
