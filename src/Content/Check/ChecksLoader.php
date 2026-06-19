<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

class ChecksLoader
{
    /**
     * @var CheckInterface[]
     */
    private array $checks;

    /**
     * @param iterable<CheckInterface> $checks
     */
    public function __construct(iterable $checks)
    {
        $this->checks = [];

        foreach ($checks as $check) {
            $this->checks[] = $check;
        }

        usort($this->checks, static function (CheckInterface $a, CheckInterface $b): int {
            return $b->getPriority() <=> $a->getPriority() ?: $a->getCode() <=> $b->getCode();
        });
    }

    /**
     * @return CheckInterface[]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}
