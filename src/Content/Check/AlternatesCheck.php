<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

class AlternatesCheck implements CheckInterface
{
    public function getCode(): string
    {
        return 'alternates';
    }

    public function getPriority(): int
    {
        return 600;
    }

    public function check(SeoCheckContext $context): SeoCheck
    {
        $alternates = $context->alternates();
        $expectedLocales = $context->expectedAlternateLocales();
        $foundLocales = $context->foundAlternateLocales($alternates);
        $missingLocales = $context->missingAlternateLocales($alternates);
        $invalidHrefs = $context->invalidAlternateHrefs($alternates);
        $details = [
            'expected' => [] !== $expectedLocales ? $expectedLocales : ['-'],
            'found' => [] !== $foundLocales ? $foundLocales : ['-'],
            'missing' => $context->limitedList($missingLocales),
            'invalid_hrefs' => $context->limitedList($invalidHrefs),
        ];

        if ([] === $expectedLocales) {
            return new SeoCheck('alternates', 'ok', 100, ['expected' => 0, 'found' => \count($foundLocales), 'missing' => 0], $details);
        }

        if ([] !== $missingLocales || [] !== $invalidHrefs) {
            return new SeoCheck('alternates', 'warning', 60, [
                'expected' => \count($expectedLocales),
                'found' => \count($foundLocales),
                'missing' => \count($missingLocales),
                'invalidHrefs' => \count($invalidHrefs),
            ], $details);
        }

        return new SeoCheck('alternates', 'ok', 100, ['expected' => \count($expectedLocales), 'found' => \count($foundLocales), 'missing' => 0], $details);
    }
}
