<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\WordStatistics;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsBundle\Model\SiteInterface;
use Softspring\CmsBundle\Render\ContentVersionRenderer;
use Softspring\CmsBundle\Render\Error\RenderErrorList;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class VersionWordStatistics
{
    public function __construct(
        private readonly ContentVersionRenderer $renderer,
        private readonly HtmlWordStatistics $statistics,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function analyze(ContentVersionInterface $version, ?string $locale = null, ?string $site = null, ?int $limit = null): array
    {
        if (null !== $limit && ($limit < 1 || $limit > 100)) {
            throw new InvalidArgumentException('The term limit must be between 1 and 100.');
        }
        $content = $version->getContent();
        if (!$content instanceof ContentInterface) {
            throw new InvalidArgumentException('The version must belong to a content element.');
        }
        $locale ??= $content->getDefaultLocale();
        if (!in_array($locale, $content->getLocales() ?? [], true)) {
            throw new InvalidArgumentException('The locale must be configured for the content.');
        }
        $selectedSite = $content->getSites()->filter(static fn(SiteInterface $item): bool => $item->isEnabled()
            && (null === $site || $item->getId() === $site)
            && in_array($locale, $item->getConfig()['locales'] ?? [], true))->first();
        if (!$selectedSite instanceof SiteInterface) {
            throw new InvalidArgumentException('An enabled content site supporting the locale is required.');
        }
        $result = ['schemaVersion' => 1, 'method' => 'deterministic', 'scope' => 'version_containers',
            'contentId' => (string) $content->getId(), 'versionId' => (string) $version->getId(),
            'versionNumber' => $version->getVersionNumber(), 'locale' => $locale, 'site' => $selectedSite->getId()];
        $errors = new RenderErrorList();
        try {
            $port = $selectedSite->getCanonicalPort();
            $request = Request::create(($selectedSite->getCanonicalScheme() ?? 'https') . '://' . $selectedSite->getCanonicalHost() . ($port ? ':' . $port : '') . '/');
            $request->setLocale($locale);
            $request->attributes->set('_cms_preview', true);
            $request->attributes->set('_sfs_cms_site', $selectedSite);
            $path = $content->getCanonicalRoutePath($locale);
            $request->attributes->set('routePath', $path);
            $request->attributes->set('_route', $path?->getRoute()->getId());
            $containers = $this->renderer->renderContainers($version, $request, $errors);
        } catch (Throwable $exception) {
            $this->logger?->warning('Unable to render CMS version word statistics.', ['version' => $result['versionId'], 'exception' => $exception]);

            return $result + ['ok' => false, 'error' => 'The content version could not be rendered.'];
        }
        if ($errors->hasErrors()) {
            return $result + ['ok' => false, 'error' => 'Some content modules could not be rendered.'];
        }

        return $result + ['ok' => true, 'containers' => array_keys($containers)]
            + $this->statistics->analyze(implode("\n", $containers), $locale, $limit);
    }
}
