<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Seo;

class SeoAnalysisSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serializeAnalysis(SeoAnalysisResult $analysis): array
    {
        return [
            'score' => $analysis->score,
            'severity' => $analysis->severity,
            'metrics' => $analysis->metrics,
            'sources' => $analysis->sources,
            'checks' => array_map(fn (SeoCheck $check): array => $this->serializeCheck($check), $analysis->checks),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeCheck(SeoCheck $check): array
    {
        return [
            'code' => $check->code,
            'severity' => $check->severity,
            'score' => $check->score,
            'data' => $check->data,
            'details' => $check->details,
        ];
    }
}
