<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content;

use Softspring\CmsBundle\Model\RoutePathInterface;
use Softspring\CmsBundle\Model\SiteInterface;

final readonly class PageUrl
{
    public function __construct(
        public SiteInterface $site,
        public RoutePathInterface $routePath,
        public string $path,
        public string $url,
        public string $locale,
    ) {}
}
