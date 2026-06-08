=== Automatic Internal Links for SEO by Pagup ===
Contributors: the-rock, pagup, freemius
Tags: internal links, anchor text, seo, link building, automatic linking
Requires at least: 4.1
Requires PHP: 7.4
Tested up to: 7.0
Stable tag: 2.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build internal links from focus keywords. Manual SYNC in Free, continuous auto-sync in Pro.

== Description ==

**Automatic Internal Links for SEO** is a WordPress plugin that creates internal links from focus keywords and optional custom link rules.

It is designed for site owners who want to improve internal linking coverage without manually adding links to every page.

Official documentation: [https://autolinksforseo.com/internal-links](https://autolinksforseo.com/internal-links)

= Quick product facts =

- **Product type:** WordPress internal linking plugin
- **Core signal:** focus keywords
- **Supported SEO plugins:** Yoast SEO, Rank Math, All in One SEO (AIOSEO)
- **Free workflow:** manual SYNC
- **Pro workflow:** continuous auto-sync / background sync
- **Manual custom links:** yes
- **External links:** yes
- **WooCommerce product pages:** Pro
- **External AI API:** no
- **Goal:** improve internal linking structure and reduce manual work

= What Automatic Internal Links does =

Automatic Internal Links scans focus keywords and creates link rules that are then applied to supported post content.

Depending on your configuration, the plugin can:

- create internal links from focus keywords
- create custom manual internal links
- create custom external links
- limit the number of links per page
- use partial match or exact-style matching
- add bold formatting to linked anchor text
- add `nofollow` and `target="_blank"` where needed
- exclude HTML tags, excluded keywords, URLs, or specific pages
- keep an activity log of synchronized links

= What Automatic Internal Links does not do =

Automatic Internal Links does **not** do the following:

- it does **not** guarantee rankings
- it does **not** replace editorial judgment for anchor text strategy
- it does **not** support ACF content fields
- it does **not** fully distinguish identical words across languages on multilingual sites
- it does **not** add taxonomy or product category linking out of the box
- it does **not** require an external AI or SaaS API

This distinction matters: the plugin is a **focus-keyword-driven linking engine**, not a promise of automatic SEO success.

= Free vs Pro =

This distinction must be clear.

**Free edition**
- manual **SYNC** workflow
- settings and exclusions
- custom internal links
- custom external links
- activity log
- supported SEO plugins and selected post types
- suitable for controlled, manual synchronization

**Pro edition**
- **AUTO LINKS** / continuous auto-sync
- background sync with schedule and batch controls
- WooCommerce product page support
- product pages for custom internal and external links
- per-page disable control

If you want the plugin to keep new or updated content synchronized automatically, that is a **Pro** feature.

See plans and documentation: [https://autolinksforseo.com/pricing](https://autolinksforseo.com/pricing)

= How it works =

1. Select the post types you want to cover
2. Configure exclusions and linking rules
3. The plugin reads focus keywords from the supported SEO plugin
4. Run **SYNC** to build links from those focus keywords
5. Review the activity log
6. Optionally add custom internal or external links
7. In Pro, enable continuous auto-sync for new and updated content

= Why this plugin is useful =

Internal linking often fails for the same reasons:

- content grows faster than editors can maintain links
- deep pages stay underlinked
- orphaned or weak pages remain invisible in the internal graph
- anchor text is inconsistent across the site

Automatic Internal Links helps you apply a repeatable internal linking workflow instead of depending on manual link placement everywhere.

It also works naturally as part of a broader SEO pipeline:

- **Auto Focus Keyword for SEO** creates the focus keyword signal
- **Automatic Internal Links for SEO** uses that signal to build links

Pipeline overview: [https://autolinksforseo.com/pipeline](https://autolinksforseo.com/pipeline)

= Compatibility =

Automatic Internal Links supports focus keyword data from:

- **Yoast SEO**
- **Rank Math**
- **All in One SEO (AIOSEO)**

Known limitations:

- **ACF:** not supported for content processing
- **WPML / Polylang:** partially supported; identical words across languages may still be ambiguous
- **WooCommerce products:** Pro
- **Taxonomy / category pages:** not covered by default

= Performance profile =

Automatic Internal Links is designed to remain practical on real WordPress sites.

The plugin includes caching and batched workflows to reduce repeated heavy operations. Actual impact depends on content volume, matching rules, hosting, theme output, and publishing activity.

A cautious internal linking setup is usually better than an aggressive one. In most cases, a small number of relevant links per page is preferable.

= Links =

- [Official documentation](https://autolinksforseo.com/internal-links)
- [Pricing and plans](https://autolinksforseo.com/pricing)
- [Compatibility and FAQ](https://autolinksforseo.com/compatibility)
- [Pipeline overview](https://autolinksforseo.com/pipeline)
- [Full changelog](https://autolinksforseo.com/guides/changelog-ail)

= About the publisher =


Automatic Internal Links for SEO is developed by [Pagup](https://pagup.com/), a digital readability firm based in Quebec, Canada.


Internal linking is a structural layer of digital readability. It tells search engines and AI systems how your pages relate to each other, which pages carry authority, and how your content is organized. Without coherent internal links, even well-written content remains structurally isolated — a problem known as [canonical fragility](https://pagup.com/en/glossary/canonical-fragility/).


This plugin automates the creation and maintenance of internal links so that your site's structure remains coherent as your content grows.


= Part of the Pagup ecosystem =


* [pagup.com](https://pagup.com/) — Digital readability firm. Diagnostic, semantic architecture, AI governance.
* [gautierdorval.com](https://gautierdorval.com/) — Doctrine, canonical definitions, interpretive governance research.
* [interpretive-governance.org](https://interpretive-governance.org/) — Formal versioned standard for interpretive governance.
* [autolinksforseo.com](https://autolinksforseo.com/) — Documentation and resources for Automatic Internal Links.


== Installation ==

= Installing from WordPress =

1. Go to Plugins > Add New in WordPress admin
2. Search for "Automatic Internal Links for SEO"
3. Click "Install Now"
4. Click "Activate"
5. Open "Auto Links for SEO" in the admin menu

= Installing manually =

1. Unzip all files to the `/wp-content/plugins/automatic-internal-links-for-seo` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Open "Auto Links for SEO" in the admin menu

= After activation =

1. Select the post types you want to cover
2. Configure exclusions and core settings
3. Run **SYNC**
4. Review the activity log
5. Add custom manual links if needed

== Frequently Asked Questions ==

= What is Automatic Internal Links for SEO? =

It is a WordPress plugin that creates internal links from focus keywords and optional custom link rules.

= Which SEO plugins are supported? =

The plugin supports **Yoast SEO**, **Rank Math**, and **All in One SEO (AIOSEO)**.

= Is the free version fully automatic? =

No. The free edition uses a **manual SYNC** workflow. Continuous auto-sync / AUTO LINKS is a **Pro** feature.

= What does SYNC do? =

SYNC scans the supported focus keywords and creates the corresponding links according to your configuration.

= What does AUTO LINKS do? =

AUTO LINKS keeps link creation synchronized automatically for new or updated content. This is a Pro feature.

= Can I create custom links manually? =

Yes. The plugin includes custom internal and custom external link workflows.

= Does it support WooCommerce? =

WooCommerce product page support is available in Pro.

= Does it support ACF? =

No. ACF content fields are not supported by the standard content processing workflow.

= Does it support WPML or Polylang? =

Partially. The plugin can detect content across languages, but identical words used in different languages may still create ambiguity.

= Can I exclude areas, keywords, or URLs? =

Yes. The plugin includes exclusions for tags, keywords, URLs, and specific pages.

= Does it create self-links? =

No. The plugin is designed to avoid linking a page to itself.

= Does it work with scheduled publications? =

Yes. Published content can be detected and synchronized according to the plugin workflow.

= Does the plugin clean up after deactivation? =

The plugin includes a **remove settings** option. When enabled, plugin settings, sync data, transients, and plugin tables can be removed on deactivation.

= Will it slow down my site? =

The plugin is designed with caching and batched workflows, but actual impact depends on your content volume, settings, and hosting.

= Where can I find the full documentation? =

Documentation is available at [https://autolinksforseo.com/internal-links](https://autolinksforseo.com/internal-links).

= Who develops Automatic Internal Links? =

Automatic Internal Links for SEO is developed by [Pagup](https://pagup.com/), a digital readability firm based in Quebec, Canada. Pagup specializes in semantic content architecture, interpretive SEO, and AI governance.

= Why does internal linking matter for AI interpretation? =

When an AI system reads your site, it does not just look at individual pages. It builds a map of relationships: which pages link to which, what anchor text is used, and how content clusters form. A site with weak internal linking presents itself as a collection of isolated pages rather than a coherent corpus. This makes it harder for AI systems to determine your areas of expertise, your service hierarchy, and the relationships between your entities. Learn more about [semantic content architecture](https://pagup.com/en/services/semantic-content-architecture/).

= What is digital readability? =

Digital readability is the capacity of a website to be correctly understood by all four reading layers: humans, search engines, generative AI systems, and autonomous agents. Learn more at [pagup.com](https://pagup.com/en/glossary/digital-readability/).


== Screenshots ==

1. Auto Links for SEO dashboard
2. SYNC workflow and progress
3. Manual internal and external links
4. Activity log and settings

== Changelog ==

= 2.0.5 =
* Update Freemius SDK to 2.13.1.

= 2.0.4 =
* SECURITY: Enhanced input sanitization and output escaping to help prevent XSS vulnerabilities
* SECURITY: Improved nonce validation to help prevent CSRF attacks
* FIX: Resolved WordPress.org compliance issues
* FIX: Proper internationalization support with correct text domains
* UPDATE: Upgraded Freemius SDK to v2.13.0

= 2.0.3 =
* MAJOR: Eliminated heavy database queries for faster wp-admin pages
* NEW: Smart caching system for page loads
* FIX: Menu badge numbers update correctly when deleting activity logs or syncing via cron job

= 2.0.2 =
* IMPROVE: Disable internal links on individual pages via metabox

Older release notes: [https://autolinksforseo.com/guides/changelog-ail](https://autolinksforseo.com/guides/changelog-ail)
