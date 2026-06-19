<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site;

class SiteAnalysisStorage
{
    public function __construct(
        private readonly string $storageDir,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function load(string $siteId): ?array
    {
        $file = $this->file($siteId);
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return \is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $analysis
     */
    public function save(string $siteId, array $analysis): void
    {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0o775, true);
        }

        file_put_contents($this->file($siteId), json_encode($analysis, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
    }

    private function file(string $siteId): string
    {
        return rtrim($this->storageDir, '/') . '/' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $siteId) . '.json';
    }
}
