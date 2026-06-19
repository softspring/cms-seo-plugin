# CMS SEO Plugin

Adds an SEO tab to CMS content administration screens.

The first version analyzes each published CMS URL for the content and runs deterministic checks against the returned
HTML: HTTP status, title, meta description, canonical URL, alternate language URLs, H1 usage, heading structure,
image alt text, robots meta tag and approximate body word count.

The dashboard also supports site-level checks for `robots.txt`, sitemap indexes, configured sitemaps and root slash
redirects. Site analysis is optional and configurable per project through `sfs_cms_seo.site_analysis`.

The internal service split is intentionally provider-friendly:

- `PageUrlResolver` finds published CMS URLs for a content item.
- `PublishedPageFetcher` downloads the published HTML.
- `BasicSeoAnalyzer` loads tagged checks, executes them and calculates scores.
- `CheckInterface` allows projects and plugins to add custom checks with the `sfs_cms_seo.check` service tag.
- `SiteAnalyzer` loads tagged site checks through the `sfs_cms_seo.site_check` service tag.

Future integrations can add AI, SEMrush, Ahrefs or other providers without changing the admin route or tab contract.
