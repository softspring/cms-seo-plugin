<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site;

final readonly class PublicResource
{
    public function __construct(
        public string $url,
        public ?int $statusCode,
        public ?string $contentType,
        public ?string $content,
        public ?string $location = null,
        public ?string $error = null,
    ) {
    }

    public function isSuccessful(): bool
    {
        return null !== $this->statusCode && $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isXml(): bool
    {
        return null !== $this->contentType && str_contains(strtolower($this->contentType), 'xml');
    }

    public function isPlainText(): bool
    {
        return null !== $this->contentType && str_contains(strtolower($this->contentType), 'text/plain');
    }
}
