<?php
/**
 * Title: Section header
 * Slug: aktheme/section-header
 * Description: Eyebrow, section title and subtitle, centred, as every AK section opens.
 * Categories: ak-sections
 * Keywords: ak, section
 * Viewport Width: 1440
 *
 * @package AK\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"className":"ak-section-head","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained","contentSize":"var(--wp--style--global--content-size)"}} -->
<div class="wp-block-group ak-section-head">
<!-- wp:paragraph {"className":"is-style-paragraph-eyebrow"} -->
<p class="is-style-paragraph-eyebrow">Eyebrow</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Section title</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-paragraph-section-sub"} -->
<p class="is-style-paragraph-section-sub">Short supporting text for this item. Replace it with your own copy.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
