<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

interface CheckInterface
{
    public function getCode(): string;

    public function getPriority(): int;

    public function check(SeoCheckContext $context): SeoCheck;
}
