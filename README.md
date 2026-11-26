# AK Theme

A reusable WordPress block theme framework. Start each client site from it: it has the design tokens, the section
blocks, the shared (synced) sections, the header, the footer and the templates, with neutral placeholder content you
replace per client.

- **Author:** [Anup Kankale](https://anupkankale.com)
- **Requires:** WordPress 6.6+ (6.9+ for the AI abilities), PHP 8.2+
- **License:** [GPL-2.0-or-later](LICENSE)

## What's in it

| Path | What it is |
| --- | --- |
| `theme.json` | Every design token (colours, type, spacing, radius, shadow, widths). Change the brand here first. |
| `parts/` | `header.html` (pill header: logo, navigation, CTA button), `footer.html` (link columns, newsletter, offices, regions, copyright), `promo-bar.html`. Each one points at its markup in `patterns/header.php`, `footer.php` and `promo-bar.php`, so links and images follow the site address. |
| `templates/` | `index`, `front-page`, `home`, `page`, `single` (blog article with TOC and related posts), `archive`, `search`, `404`, `no-title`. |
| `src/blocks/` | 18 `ak/*` section blocks: hero, section, tabs, step-cards, highlights, specializations, cost-banner, cta, faq (with FAQPage schema), testimonials, video gallery, achievements, process, usp-bento, who-can-join, analytics, subnav, float-contact. |
| `patterns/` | Section patterns and the sources of the synced sections (offer banner, testimonials, CTA, achievements, photo). |
| `inc/` | PHP (`AK\Theme\`): `Core/` (assets, menus, setup, settings), `Modules/Abilities`, `Blocks`, `Menus`, `Patterns`, `Media`, `Blog`, `Shortcodes`. |
| `src/css/`, `src/js/` | Theme-wide styles and scripts (design system, header, footer, mobile menu, footer accordion). |
| `assets/images/theme/` | Generic icons and `photo-placeholder.webp`. |
| `bin/rebrand.js` | Renames the framework for a client (see below). |
| `tests/` | PHPUnit (`tests/php`), Jest (`tests/js`), Playwright (`tests/e2e`). |

Header and footer menus are real WordPress menus (Appearance > Menus), seeded once with placeholder links:
`primary`, `footer-main`, `footer-col-2` to `footer-col-4`, `footer-region-1` to `footer-region-3`, and `blog`.

Shared sections (offer banner, testimonials, CTA, achievements, photo) become synced patterns on the first admin visit,
so one edit in Patterns > My patterns changes every page that uses them.

## AI abilities

The design (colours, fonts, global styles), the header and footer, the menus, the shared sections and the pages can be
read and changed through the WordPress Abilities API, for example by an AI agent connected through the MCP Adapter
plugin. Changes are saved like Site Editor changes, with revisions, never in the theme's files. See
[docs/abilities.md](docs/abilities.md).

Site-specific behaviour (the blog article inserts, the company LinkedIn link, the YouTube consent notice) is in the
theme settings (`aktheme_settings`), not in code: see the same page.

## Start a new client site

1. Copy this folder (without `node_modules`, `vendor`, `assets/build`) and rebrand the copy:

   ```bash
   npm ci && composer install
   npm run rebrand -- --name="Acme Studio"            # add --dry-run to preview
   composer dump-autoload && npm run lint:php:fix
   ```

   This sets the theme name, slug and text domain (`acme-studio`), PHP namespace (`AcmeStudio\Theme`), PHP prefixes
   (`acme_studio_`, `ACME_STUDIO_`) and the block titles. Block names (`ak/*`) and CSS classes (`ak-*`) stay as they
   are: visitors never see them. Run it once, before any content is created.
2. Rename the folder to the new slug and put it in the client's `wp-content/themes/`.
3. Set the brand in `theme.json` (colours, fonts) and add the client's logo in Site Editor > Header.
4. Replace the placeholder text in the header, footer, menus and block defaults (`src/blocks/*/default.html`).
5. `npm run build:prod`, then activate the theme.

## Develop

```bash
npm run build:prod              # assets, then blocks (assets/build is gitignored)
npm run lint:all                # css, js, php, package.json, design tokens
npm run test:js                 # Jest
composer phpcs && composer phpstan
```

More: [docs/getting-started.md](docs/getting-started.md), [docs/design-system.md](docs/design-system.md),
[DEVELOPMENT.md](DEVELOPMENT.md).

## Credits

`assets/fonts/twemoji-country-flags.woff2` is the "Twemoji Country Flags" font from
[country-flag-emoji-polyfill](https://github.com/talkjs/country-flag-emoji-polyfill) (code MIT; flag artwork
[Twemoji](https://github.com/twitter/twemoji), [CC-BY 4.0](https://creativecommons.org/licenses/by/4.0/)). It draws
the flags in the advisor form's country-code list on Windows.
