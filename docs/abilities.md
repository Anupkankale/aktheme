# AI abilities

The theme registers its design and content as [WordPress abilities](https://developer.wordpress.org/apis/abilities-api/)
(WordPress 6.9 or later). An AI agent, or any other Abilities API client, can find out what the site is built from
and change its colours, fonts, header, footer, menus, shared sections and pages without touching the theme's files.

Code: `inc/Modules/Abilities/`. Tests: `tests/php/inc/Modules/Abilities/AbilitiesTest.php`.

## How a change is stored

Every change is saved where the Site Editor saves it, through the core REST controllers:

| What               | Stored as                                                                | Undo                                                      |
| ------------------ | ------------------------------------------------------------------------ | --------------------------------------------------------- |
| Colours, fonts, global styles | The theme's `wp_global_styles` post (the user layer on top of `theme.json`) | `aktheme/restore-site-design`, or Styles > Revisions |
| Header, footer, other parts   | A `wp_template_part` post that replaces `parts/<slug>.html`  | `aktheme/reset-template-part` (back to the theme's version) |
| Menu links                    | The menu assigned to the location (Appearance > Menus)      | Call `aktheme/update-menu` again with the old links     |
| Shared sections               | Their synced pattern (`wp_block`)                           | Post revisions; the ability also returns the old content |
| Pages                         | The page                                                    | Page revisions                                          |
| Theme settings                | The `aktheme_settings` option                               | Call `aktheme/update-theme-settings` again              |

So a theme update never overwrites the changes, an editor can see and revert each one, and the same permission
checks and sanitising apply as in the editor. Users without `unfiltered_html` get kses on content. Design changes are
filtered for unsafe CSS for every user, because input from an agent is never treated as trusted CSS.

## The abilities

All of them are in the `aktheme` category and shown in REST (`/wp-json/wp-abilities/v1/abilities`).

| Ability                          | Does                                                              | Needs                | Annotations          |
| -------------------------------- | ----------------------------------------------------------------- | -------------------- | -------------------- |
| `aktheme/get-site-design`        | Palette, fonts, font and spacing sizes, global styles, revisions  | `edit_theme_options` | read-only            |
| `aktheme/update-site-design`     | Change or add colours and font sizes; merge or replace styles     | `edit_theme_options` | destructive          |
| `aktheme/restore-site-design`    | Restore a design revision (default: undo the last change)         | `edit_theme_options` | destructive, idempotent |
| `aktheme/list-template-parts`    | Header, footer, ... and whether each was changed on this site     | `edit_theme_options` | read-only            |
| `aktheme/get-template-part`      | A part's blocks, theme patterns expanded                          | `edit_theme_options` | read-only            |
| `aktheme/update-template-part`   | Save new blocks for a part (or create one)                        | `edit_theme_options` | destructive, idempotent |
| `aktheme/reset-template-part`    | Back to the theme's version of a part                             | `edit_theme_options` | destructive, idempotent |
| `aktheme/list-menus`             | Menu locations and their links                                    | `edit_theme_options` | read-only            |
| `aktheme/update-menu`            | Replace the links of a location (two levels)                      | `edit_theme_options` | destructive, idempotent |
| `aktheme/list-building-blocks`   | Patterns, shared sections, AK blocks, block style variations      | `edit_pages`         | read-only            |
| `aktheme/update-section`         | New blocks for a shared section (changes every page using it)     | `edit_theme_options` | destructive, idempotent |
| `aktheme/get-pages`              | Find pages, or one page with its blocks                           | `edit_pages`         | read-only            |
| `aktheme/create-page`            | A page from a theme pattern or blocks, as a draft by default      | `edit_pages`         |                      |
| `aktheme/update-page`            | A page's title, blocks or status                                  | `edit_pages`         | destructive, idempotent |
| `aktheme/get-theme-settings`     | The theme settings                                                | `manage_options`     | read-only            |
| `aktheme/update-theme-settings`  | Change some theme settings                                        | `manage_options`     | idempotent           |

Each ability's description, which agents read before choosing a tool, explains its input with examples. Its input
schema rejects malformed input before anything runs. For example, a palette colour must be a hex, `rgb()`/`hsl()` or
`var(--wp--preset--color--…)` value.

## Connecting an AI agent (MCP)

1. Install and activate the [MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin. Its default server lists
   every ability that has `meta.mcp.public`, which all of the theme's abilities have.
2. Create an application password for a user with the right role. An Editor can change pages only. An Administrator
   can also change the design, the header, the footer and the menus.
3. Point the MCP client at `https://<site>/wp-json/mcp/mcp-adapter-default-server`, or run
   `wp mcp-adapter serve --server=mcp-adapter-default-server` over STDIO.

The agent then uses the adapter's three tools: discover the abilities, get one ability's schema, and run it. Logging
in is not the same as being allowed: each ability checks the user's capability when it runs.

A good first prompt: *"Call aktheme/get-site-design and aktheme/list-building-blocks, then suggest changes before
making any."*

## Limiting what agents can do

Abilities that change something are destructive. To stop agents on MCP from making changes, for example on a
production site, add this to a must-use plugin:

```php
// Offer only the abilities that read over MCP.
add_filter( 'aktheme_abilities_mcp_public', fn ( bool $is_public, string $name, bool $is_readonly ) => $is_readonly, 10, 3 );
```

To remove abilities completely, or to add a client's own:

```php
add_filter(
	'aktheme_abilities',
	fn ( array $classes ) => array_diff( $classes, [ AK\Theme\Modules\Abilities\Content\UpdatePage::class ] )
);
```

A client ability extends `AK\Theme\Modules\Abilities\Ability`. It needs `slug()`, `label()`, `description()`,
`input_schema()`, `output_schema()` and `execute()`. It can override `capability()`, `is_readonly()`, `destructive()`
and `idempotent()`. Make changes through `$this->rest()` (a core REST route) so they keep revisions and permission
checks.

## Theme settings

The site-specific behaviour that used to be hard-coded for one client is now in `aktheme_settings`:

| Key                    | Default | Does                                                                          |
| ---------------------- | ------- | ----------------------------------------------------------------------------- |
| `blog_article_inserts` | `false` | Program card, skills banner and "Recommended reading" inside every blog post |
| `company_linkedin_url` | `''`    | Author box link when the author's Website isn't a LinkedIn profile           |
| `youtube_consent`      | `true`  | Ask before any YouTube player loads (gallery blocks and Embed blocks)        |

Sites that ran the theme before 1.3.0 keep `blog_article_inserts` on (set once, on the first wp-admin visit). To lock
a value per environment, use the `aktheme_setting` filter.
