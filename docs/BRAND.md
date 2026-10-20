# Mammoth WP brand identity

Short reference. The brand repo (gitlab.motivar.io/mammothwp/brand, mirrored to
github.com/mammothwp/brand) is the source of truth for tokens, logos and plugin
asset templates; never copy hex values or assets into product code by hand.

## Name

- **Mammoth WP**: always two words, capital M, capital WP.
- Never "Mammoth" alone as a product name. Never "MammothWP" in prose; that
  spelling exists only for the PHP namespace `MammothWP\`.
- Products are "Mammoth WP <Function>", up to four words (Mammoth WP Share
  Checkout, Mammoth WP Acceptance Logs). Slug follows the name; text domain = slug.
- "WP" is fine in names and slugs. The full word "WordPress" never appears in a
  name, slug, handle or prefix.

## Handles and domains

- GitHub org, Packagist vendor and wordpress.org user: `mammothwp`.
- Composer packages: `mammothwp/<slug>`.
- mammothwp.com is the only primary domain. mammoth-wp.com is a 301 redirect
  only and is never linked.
- Contact: info@mammothwp.com.

## Code identifiers

| Thing | Convention |
|---|---|
| PHP namespace | `MammothWP\` |
| PHP globals, options, transients, cron hooks, meta keys, hooks | `mmwp_` (library hooks: `mmwp_kit_`) |
| CSS classes, JS/CSS handles, HTML ids | `mmwp` followed by a hyphen |
| REST namespace | `mmwp/v1` |
| WP-CLI root | `wp mmwp` |
| Abilities | `mmwp/*` |

Never a three-letter prefix.

## Visual identity

- Warm ivory background; espresso/umber brown as the brand colour (brand umber
  `#4e2d16` on light, `#a87a52` on dark).
- Type: Commissioner for UI and headings, JetBrains Mono for code. Both
  self-hosted, never loaded from Google Fonts.
- Logo: geometric block mammoth in profile plus the "Mammoth WP" wordmark.
- Plugin icons and banners use the "split panel" layout: brown square with the
  mark, name on ivory.

## wp-admin

Plugins use core WordPress admin UI only: no brand colours, fonts or custom
styling inside wp-admin. The brand appears only in the shared
"Mammoth WP → Support" screen and the Mammoth WP menu icon.

## Tone

Plain, direct, technical; light dry humour allowed. No hype, no "AI-powered",
no exclamation-mark marketing. Positioning: plugins for people who read the
source. Heavy name, light plugins.
