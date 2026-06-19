<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Seo;

use Softspring\CmsSeoPlugin\Content\ContentSeoAnalysisRunner;
use Softspring\CmsSeoPlugin\Site\SiteAnalysisRunner;

class SyncSeoAnalysisDispatcher implements SeoAnalysisDispatcherInterface
{
    public function __construct(
        private readonly SiteAnalysisRunner $siteAnalysisRunner,
        private readonly ContentSeoAnalysisRunner $contentAnalysisRunner,
    ) {
    }

    public function analyze(bool $sites, bool $contents, bool $async): array
    {
        $result = [
            'async' => false,
            'sites' => null,
            'contents' => null,
        ];

        if ($sites) {
            $result['sites'] = [
                'count' => \count($this->siteAnalysisRunner->analyzeAll()),
            ];
        }

        if ($contents) {
            $result['contents'] = $this->contentAnalysisRunner->analyzeAll();
        }

        return $result;
    }
}
