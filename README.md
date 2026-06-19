# CMS SEO Plugin (Experimental)

[![Latest Stable](https://img.shields.io/packagist/v/softspring/cms-seo-plugin?label=stable&style=flat-square)](https://github.com/softspring/cms-seo-plugin/releases)
[![Latest Unstable](https://img.shields.io/packagist/v/softspring/cms-seo-plugin?label=unstable&style=flat-square&include_prereleases)](https://github.com/softspring/cms-seo-plugin/releases)
[![License](https://img.shields.io/packagist/l/softspring/cms-seo-plugin?style=flat-square)](https://github.com/softspring/cms-seo-plugin/blob/6.0/LICENSE)
[![PHP Version](https://img.shields.io/packagist/dependency-v/softspring/cms-seo-plugin/php?style=flat-square)](https://github.com/softspring/cms-seo-plugin/blob/6.0/composer.json)
[![Downloads](https://img.shields.io/packagist/dt/softspring/cms-seo-plugin?style=flat-square)](https://packagist.org/packages/softspring/cms-seo-plugin)
[![CI](https://img.shields.io/github/actions/workflow/status/softspring/cms-seo-plugin/ci.yml?branch=6.0&style=flat-square&label=CI)](https://github.com/softspring/cms-seo-plugin/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/softspring/cms-seo-plugin?branch=6.0&style=flat-square)](https://app.codecov.io/gh/softspring/cms-seo-plugin/tree/6.0)

> **Experimental package:** this plugin is in active development and its configuration, checks, storage format, UI, and extension points may change before a stable release.

`softspring/cms-seo-plugin` adds SEO analysis tools to Softspring CMS administration.

It analyzes published CMS URLs and reports deterministic checks for HTTP status, HTML content type, title, meta description, canonical URL, alternate language URLs, H1 usage, heading structure, image alt text, robots meta tag, and approximate body word count.

It also supports site-level checks for `robots.txt`, sitemap indexes, configured sitemaps, and root slash redirects.

## Installation

```bash
composer require softspring/cms-seo-plugin:^6.0@dev
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

## Extension Points

Add content SEO checks by implementing `Softspring\CmsSeoPlugin\Content\Check\CheckInterface`. Services are autoconfigured with the `sfs_cms_seo.check` tag.

Add site SEO checks by implementing `Softspring\CmsSeoPlugin\Site\Check\SiteCheckInterface`. Services are autoconfigured with the `sfs_cms_seo.site_check` tag.

Replace `Softspring\CmsSeoPlugin\Seo\SeoAnalysisDispatcherInterface` when a project needs asynchronous analysis or an external provider.

## Features

See [FEATURES.md](FEATURES.md) for the functional scope of this package.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

[Report issues](https://github.com/softspring/cms-seo-plugin/issues) and [send Pull Requests](https://github.com/softspring/cms-seo-plugin/pulls)

## Security

See [SECURITY.md](SECURITY.md).

## License

This package is free and released under the [AGPL-3.0 license](LICENSE).
