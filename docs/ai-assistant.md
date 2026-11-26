# AI writing assistant

The theme adds Claude-powered writing help to the block editor. Nothing loads for visitors: the script runs only in
the editor, for users who can edit posts.

Code: `inc/Modules/AI/` (PHP) and `src/js/editor/ai-assistant.js` (editor). Tests: `tests/php/inc/Modules/AI/` and
`tests/js/ai-utils.test.js`.

## What it does

| Where                                   | Action                 | Result                                                                    |
| --------------------------------------- | ---------------------- | ------------------------------------------------------------------------- |
| **AK AI** sidebar (sparkle icon, top bar) | Meta description     | About 155 characters from the page, saved as the excerpt                  |
|                                         | Title ideas            | 5 SEO titles; click one to use it                                         |
|                                         | Write a section        | A section (heading, paragraphs, lists) from a short brief                 |
|                                         | FAQ (with schema)      | 3 to 10 questions, inserted as an AK FAQ block (FAQPage structured data)  |
| Paragraph and heading toolbar           | Rewrite with AI        | Improve, shorten, expand, fix grammar or make more SEO-friendly           |
| Image toolbar                           | Write alt text with AI | One-sentence alt text from the image itself                               |

Every result opens in a preview first. Nothing changes until you click Replace, Insert or Use, and Undo works as usual.
The assistant is told not to invent prices, statistics, testimonials or contact details, and to write `[placeholder]`
where a section needs one. Still check facts before publishing.

## Set it up

1. Create an API key at [console.anthropic.com](https://console.anthropic.com).
2. Give it to the theme, in one of three ways (the first one found wins):
   - **wp-config.php (recommended)**: `define( 'AKTHEME_ANTHROPIC_API_KEY', 'sk-ant-…' );` keeps the key out of the
     database.
   - The `ANTHROPIC_API_KEY` environment variable.
   - **Appearance > AK AI**: the key is stored encrypted (AES-256-GCM, keyed by the site's `AUTH_KEY`/`AUTH_SALT`) in
     its own option, `aktheme_ai_key`. That option is not autoloaded, and it is never exposed by `/wp/v2/settings` or
     the theme-settings abilities. The screen only ever shows the key's last four characters.
3. Click **Test connection** in Appearance > AK AI.

## Model and cost

The model is set in Appearance > AK AI (`ai_model` setting). The default is `claude-opus-5-5`; Sonnet 5.5, Haiku 5.5
and Fable 5.2 are also available. Requests use **low effort**, because the tasks are short. That keeps them fast and
cheap: a meta description or rewrite is typically a fraction of a cent.

For Opus, Sonnet and Fable, **server-side refusal fallbacks** are on (`fallbacks: "default"`). If a safety classifier
declines a request, the API retries it on a fallback model in the same call, instead of failing.

## Privacy

Text is sent to Anthropic only when an editor clicks one of the buttons: the page title and text, or the selected
block's text. For alt text, the image is sent too (as base64, downscaled to at most 1568 px), so it also works on local
and private sites. Nothing is sent automatically, and nothing from visitors is ever sent. Mention this in your site's
privacy policy if editors use the assistant on personal data.

## How it works

- `POST /wp-json/aktheme/v1/ai` with `{ task, input }`. It needs `edit_posts` (and `upload_files` for alt text). The
  server owns every prompt: the editor only sends the text to work on, and replies are sanitised (`wp_kses`) before
  they reach the editor. Input longer than 200,000 characters is refused, never silently cut.
- Claude is called with the official Anthropic PHP SDK (`anthropic-ai/sdk`). Its requests go through
  `wp_safe_remote_request()` (`AK\Theme\Modules\AI\Http\WpHttpClient`), so the theme does not bundle Guzzle, which
  plugins often ship in clashing versions. Requests also honour the site's proxy and SSL settings.
- Turn the assistant off with the switch in Appearance > AK AI, the `ai_enabled` setting, or the `aktheme_ai_enabled`
  filter.

## Filters

| Filter               | Use                                                                                       |
| -------------------- | ----------------------------------------------------------------------------------------- |
| `aktheme_ai_enabled` | Force the assistant on or off, e.g. per environment                                       |
| `aktheme_ai_client`  | Return your own `AK\Theme\Modules\AI\AiClient`, e.g. a gateway, or a fake in tests        |
| `aktheme_ai_timeout` | HTTP timeout in seconds (default 60)                                                      |
