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

The theme has its own MCP server, so no plugin is needed. It uses the Streamable HTTP transport, at:

```
https://<site>/wp-json/aktheme/v1/mcp
```

1. Create an application password (Users > Profile > Application Passwords) for a user with the right role. An Editor
   can change pages only. An Administrator can also change the design, the header, the footer and the menus.
   Application passwords need HTTPS, or a local site (`WP_ENVIRONMENT_TYPE` `local`).
2. Connect the client. For Claude Code:

   ```bash
   claude mcp add --transport http aktheme https://<site>/wp-json/aktheme/v1/mcp \
     --header "Authorization: Basic $(printf '%s' 'USERNAME:APPLICATION PASSWORD' | base64)"
   ```

   Appearance > AK AI shows the endpoint and this command with your username filled in. Any other MCP client that
   speaks Streamable HTTP can connect with the same URL and `Authorization` header.

Each ability is one tool, named with `__` in place of the `/` (`aktheme/update-page` becomes
`aktheme__update-page`). Tools carry the ability's input schema and the read-only, destructive and idempotent hints. A
tool call runs the ability like any other Abilities API client: core validates the input against the schema, and the
ability checks the user's capability. Logging in is not the same as being allowed.

A good first prompt: *"Call aktheme__get-site-design and aktheme__list-building-blocks, then suggest changes before
making any."*

The server is stateless and answers with JSON (no SSE stream). It handles `initialize`, `ping`, `tools/list` and
`tools/call`. Turn it off with the **Serve the MCP endpoint** switch in Appearance > AK AI, the `mcp_enabled` setting,
or the `aktheme_mcp_enabled` filter. The `aktheme_mcp_abilities` filter adds abilities from other namespaces.

The abilities are still listed in core's `/wp-json/wp-abilities/v1` REST routes. They are also offered on the
[MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin's server if a site uses that plugin, but it is not
required.

## Limiting what agents can do

Abilities that change something are destructive. To stop agents on MCP from making changes, for example on a
production site, add this to a must-use plugin:

```php
// Offer only the abilities that read over MCP (the theme's server and the MCP Adapter both honour this).
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
| `ai_enabled`           | `true`  | The AI writing assistant in the editor ([AI writing assistant](ai-assistant.md)) |
| `ai_model`             | `claude-opus-5-5` | The Claude model the assistant uses                                 |
| `mcp_enabled`          | `true`  | The built-in MCP endpoint                                                     |

Sites that ran the theme before 1.3.0 keep `blog_article_inserts` on (set once, on the first wp-admin visit). To lock
a value per environment, use the `aktheme_setting` filter.
