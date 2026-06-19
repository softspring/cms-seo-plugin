<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content;

use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Routing\UrlGenerator;

class PageUrlResolver
{
    public function __construct(
        protected UrlGenerator $urlGenerator,
    ) {}

    /**
     * @return PageUrl[]
     */
    public function resolve(ContentInterface $content): array
    {
        $urls = [];

        foreach ($content->getRoutes() as $route) {
            foreach ($route->getPaths() as $routePath) {
                if (null === $routePath->getCompiledPath()) {
                    continue;
                }

                foreach ($routePath->getSites() as $site) {
                    $path = $this->urlGenerator->getPathFixed($routePath, $site);
                    $url = $this->urlGenerator->getUrlFixed($routePath, $site);
                    $key = $site->getId() . '|' . $routePath->getLocale() . '|' . $path;

                    $urls[$key] = new PageUrl(
                        $site,
                        $routePath,
                        $this->normalizePath($path),
                        $url,
                        (string) $routePath->getLocale(),
                    );
                }
            }
        }

        return array_values($urls);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return '//' === $path ? '/' : $path;
    }
}
