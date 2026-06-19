<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

class SiteChecksLoader
{
    /**
     * @var SiteCheckInterface[]
     */
    private array $checks;

    /**
     * @param iterable<SiteCheckInterface> $checks
     */
    public function __construct(iterable $checks)
    {
        $this->checks = [];

        foreach ($checks as $check) {
            $this->checks[] = $check;
        }

        usort($this->checks, static function (SiteCheckInterface $a, SiteCheckInterface $b): int {
            return $b->getPriority() <=> $a->getPriority() ?: $a->getCode() <=> $b->getCode();
        });
    }

    /**
     * @return SiteCheckInterface[]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}
