# CMS SEO Plugin Features

Functional definition for `softspring/cms-seo-plugin`.

This package extends Softspring CMS with deterministic SEO analysis for published content URLs, configured CMS sites, and rendered content version word statistics.

## Purpose

- Help editors find SEO issues in CMS content.
- Check published pages instead of only stored CMS data.
- Surface site-level SEO infrastructure problems from the CMS admin.
- Provide extension points for project-specific checks and future external providers.

## Main Features

- SEO dashboard in CMS administration.
- SEO tab for CMS content administration screens.
- SEO tab for CMS site administration screens.
- Published page fetching through Symfony HttpClient.
- Content SEO checks for HTTP status, HTML content type, title, meta description, canonical URL, alternate language URLs, H1 usage, heading structure, image alt text, robots meta tag, and body word count.
- Site SEO checks for `robots.txt`, sitemap index reachability, sitemap index entries, configured sitemap reachability, and slash route redirects.
- Stored SEO analysis metadata for content versions.
- Content SEO tabs reuse stored analysis metadata until editors request fresh data.
- Content read screens can show stored SEO summaries for published versions.
- Stored site analysis data under the project `var/` directory.
- `sfs:cms:seo:analyze` command for content and site analysis.
- Configurable site analysis checks through `sfs_cms_seo.site_analysis`.
- Cache-busting fetch requests so analysis can bypass CDN or Varnish HTML.
- Local `.local` URL fetch rewriting through the project balancer container.

## Expected Usage

- Install it in a Symfony project that already uses `softspring/cms-bundle`.
- Register `SfsCmsSeoPlugin` as a Symfony bundle when Flex does not do it automatically.
- Use the CMS admin SEO tabs to inspect individual content and site results.
- Use the SEO dashboard to review global results and weak content URLs.
- Run `sfs:cms:seo:analyze` from maintenance jobs when analysis should be refreshed outside the admin UI.

## Extension Points

- Implement `Content\Check\CheckInterface` to add content checks.
- Implement `Site\Check\SiteCheckInterface` to add site checks.
- Replace `SeoAnalysisDispatcherInterface` to dispatch analysis asynchronously.
- Override Twig templates through Symfony template resolution.
- Replace fetcher or analyzer services in the host application when a project needs custom networking or scoring behavior.
- Add future analysis providers beside `BasicSeoAnalyzer` and merge their results in orchestration code.

## Current Limits

- Published URL checks depend on public URLs being reachable from the application runtime.
- The default dispatcher runs synchronously.
- The default checks are deterministic and do not use external SEO providers.
- The package does not ship frontend assets.

## Content version word statistics

- Analyze rendered version containers without publishing or persisting compiled output.
- Separate word frequency and semantic emphasis rankings, with counts, density and tag contexts.
- Filter built-in English and Spanish stop words, with configurable additions for any language.
- Configure heading and emphasis weights; nested formatting uses the highest weight once per occurrence.
- Reuse the VersionWordStatistics service and sfs_cms_seo_word_statistics Twig function.
- Show the same statistics in version information and the SEO Analysis tab.
- Provide an optional query to API/MCP integrations without depending on either plugin or an AI provider.
- Analyze server-rendered containers only; layout text, metadata, CSS computed visibility and semantic keyword research are outside this scope.
