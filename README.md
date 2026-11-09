# CMS SEO Plugin (Experimental)

[![Latest Stable](https://img.shields.io/packagist/v/softspring/cms-seo-plugin?label=stable&style=flat-square)](https://github.com/softspring/cms-seo-plugin/releases)
[![Latest Unstable](https://img.shields.io/packagist/v/softspring/cms-seo-plugin?label=unstable&style=flat-square&include_prereleases)](https://github.com/softspring/cms-seo-plugin/releases)
[![License](https://img.shields.io/packagist/l/softspring/cms-seo-plugin?style=flat-square)](https://github.com/softspring/cms-seo-plugin/blob/6.1/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/softspring/cms-seo-plugin/php?style=flat-square)](https://github.com/softspring/cms-seo-plugin/blob/6.1/composer.json)
[![Downloads](https://img.shields.io/packagist/dt/softspring/cms-seo-plugin?style=flat-square)](https://packagist.org/packages/softspring/cms-seo-plugin)
[![CI](https://img.shields.io/github/actions/workflow/status/softspring/cms-seo-plugin/ci.yml?branch=6.1&style=flat-square&label=CI)](https://github.com/softspring/cms-seo-plugin/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/softspring/cms-seo-plugin?branch=6.1&style=flat-square)](https://app.codecov.io/gh/softspring/cms-seo-plugin/tree/6.1)

> **Experimental package:** this plugin is in active development and its configuration, checks, storage format, UI, and extension points may change before a stable release.

`softspring/cms-seo-plugin` adds SEO analysis tools to Softspring CMS administration.

It analyzes published CMS URLs and reports deterministic checks for HTTP status, HTML content type, title, meta description, canonical URL, alternate language URLs, H1 usage, heading structure, image alt text, robots meta tag, and approximate body word count.

It also supports site-level checks for `robots.txt`, sitemap indexes, configured sitemaps, and root slash redirects.

## Documentation

Read the [experimental cms-seo-plugin guide](https://armonic.softspring.es/6.1@dev/bundles/cms-seo-plugin).

## Installation

```bash
composer require softspring/cms-seo-plugin:6.1.*@dev
```

The plugin requires `softspring/cms-bundle` and Symfony HttpClient.

Register the bundle if Symfony Flex does not do it automatically:

```php
// config/bundles.php
return [
    Softspring\CmsSeoPlugin\SfsCmsSeoPlugin::class => ['all' => true],
];
```

## Configuration

Site analysis is enabled by default. Disable it or individual checks when a project does not expose the related public resources:

```yaml
# config/packages/sfs_cms_seo.yaml
sfs_cms_seo:
    site_analysis:
        enabled: true
        checks:
            robots_reachable: true
            robots_sitemap_index: true
            sitemap_index_reachable: true
            sitemap_index_contains_configured_sitemaps: true
            sitemap_reachable: true
            slash_route_redirect: true
```

## Usage

The plugin adds SEO screens to the CMS admin:

- A global SEO dashboard.
- A SEO tab for CMS content administration.
- A SEO tab for CMS site administration.

Run all configured analysis from the command line:

```bash
php bin/console sfs:cms:seo:analyze
```

Analyze only sites or content:

```bash
php bin/console sfs:cms:seo:analyze --sites
php bin/console sfs:cms:seo:analyze --contents
```

The `--async` option is reserved for projects that replace the default dispatcher with a Messenger-backed implementation.

## Content Analysis Storage

Each content analysis run is stored in the analyzed `ContentVersion` metadata field:

```text
version.meta.sfs_cms_seo_analysis
```

The metadata payload contains the analysis timestamp, content id, version id, version number, summary, and per-URL rows with checks, metrics, and details.

When the content SEO tab is opened, the plugin stores the result in the published version. When a version-specific SEO analysis route is used, the plugin stores the result in that version.

If a version already has stored SEO analysis metadata, the admin tab reads and displays that payload without fetching the published URLs again. Editors can use **Update data** to fetch the URLs again, recalculate the checks, and replace the stored metadata.

When **Update data** targets a version that stores compiled content in the database, the admin UI shows a warning because the analyzed HTML can still come from stored compiled content.

The plugin overrides `@SfsCms/admin/content/_read_indexing.html.twig` through `templates/bundles/SfsCmsBundle/admin/content/_read_indexing.html.twig`. The content read screen shows the stored SEO summary for the published version when analysis metadata exists.

## Dashboard

The dashboard reads stored SEO analysis metadata from published versions and shows:

- General statistics for average score, analyzed content, pending content, analyzed URLs, errors, and warnings.
- Site-level SEO status for robots and sitemaps.
- Lowest scoring published content.
- Lowest scoring published URLs.

The dashboard does not recalculate analysis. Editors must open the content SEO tab and use **Update data** when they need fresh content analysis.

The fetcher appends a random `sfs_cms_seo_bust` query parameter to request URLs so analysis reads fresh HTML instead of cached CDN or Varnish content. Public URLs shown in the admin UI keep their canonical form.

## Local Fetch Behavior

When a published URL uses a `.local` host, the fetcher rewrites the request to `http://balancer:8008` and sends the original host in the `Host` header plus `X-Forwarded-Proto: https`.

This avoids resolving local development domains to `127.0.0.1` inside the PHP container, where the host nginx proxy is not listening.

## Extension Points

Add content SEO checks by implementing `Softspring\CmsSeoPlugin\Content\Check\CheckInterface`. Services are autoconfigured with the `sfs_cms_seo.check` tag.

Add site SEO checks by implementing `Softspring\CmsSeoPlugin\Site\Check\SiteCheckInterface`. Services are autoconfigured with the `sfs_cms_seo.site_check` tag.

Replace `Softspring\CmsSeoPlugin\Seo\SeoAnalysisDispatcherInterface` when a project needs asynchronous analysis or an external provider.

The implementation is split so future analysis providers can be added without changing the admin tab contract:

- `PageUrlResolver` resolves CMS URLs for a content item.
- `PublishedPageFetcher` fetches published pages.
- `BasicSeoAnalyzer` creates local checks and scores.
- `SiteAnalyzer` creates site-level checks and scores.

Future providers such as AI analysis, SEMrush, or Ahrefs should add services beside `BasicSeoAnalyzer` and merge their results in the controller or an orchestration service.

## Features

See [FEATURES.md](FEATURES.md) for the functional scope of this package.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

[Report issues](https://github.com/softspring/cms-seo-plugin/issues) and [send Pull Requests](https://github.com/softspring/cms-seo-plugin/pulls)

## Security

See [SECURITY.md](SECURITY.md).

## License

This package is free and released under the [AGPL-3.0 license](LICENSE).
