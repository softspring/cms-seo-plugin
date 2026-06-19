<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Site\Check;

use Softspring\CmsSeoPlugin\Seo\SeoCheck;

interface SiteCheckInterface
{
    public function getCode(): string;

    public function getPriority(): int;

    public function check(SiteCheckContext $context): SeoCheck;
}
