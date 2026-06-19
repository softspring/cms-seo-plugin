<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Controller\Admin;

use Softspring\CmsBundle\Config\CmsConfig;
use Softspring\CmsBundle\Model\SiteInterface;
use Softspring\CmsSeoPlugin\Site\SiteAnalysisRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SiteSeoController extends AbstractController
{
    public function __construct(
        private readonly CmsConfig $cmsConfig,
        private readonly SiteAnalysisRunner $siteAnalysisRunner,
    ) {
    }

    public function seo(Request $request): Response
    {
        $site = $this->loadSite($request);
        $refresh = $request->isMethod('POST') && $request->request->getBoolean('refresh');

        if ($refresh) {
            if (!$this->isCsrfTokenValid('sfs_cms_seo_analyze_site_'.$site->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $row = $this->siteAnalysisRunner->analyze($site);
        } else {
            $row = $this->siteAnalysisRunner->stored($site);
        }

        return $this->render('@SfsCmsSeoPlugin/admin/site/seo.html.twig', [
            'site' => $site,
            'row' => $row,
            'analysis' => $row['analysis'] ?? [],
            'loaded_from_storage' => !$refresh && null !== ($row['analyzedAt'] ?? null),
        ]);
    }

    private function loadSite(Request $request): SiteInterface
    {
        $siteId = (string) $request->attributes->get('site');
        $site = '' !== $siteId ? $this->cmsConfig->getSite($siteId, false) : null;

        if (!$site instanceof SiteInterface) {
            throw new NotFoundHttpException('Site not found.');
        }

        return $site;
    }
}
