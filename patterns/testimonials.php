<?php
/**
 * Title: Testimonials
 * Slug: aktheme/testimonials
 * Description: Three testimonial cards: a grid on desktop, a swipeable row on phones.
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
<!-- wp:group {"align":"full","className":"ak-section-band","layout":{"type":"constrained","contentSize":"var(--wp--style--global--wide-size)"}} -->
<div class="wp-block-group alignfull ak-section-band">
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

<!-- wp:group {"className":"is-style-group-testimonial-grid","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-group-testimonial-grid">
<!-- wp:quote {"className":"is-style-quote-testimonial"} -->
<blockquote class="wp-block-quote is-style-quote-testimonial"><!-- wp:paragraph -->
<p>Short supporting text for this item. Replace it with your own copy.</p>
<!-- /wp:paragraph --><cite><strong>Name Surname</strong><br>Role, Company</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote {"className":"is-style-quote-testimonial"} -->
<blockquote class="wp-block-quote is-style-quote-testimonial"><!-- wp:paragraph -->
<p>Short supporting text for this item. Replace it with your own copy.</p>
<!-- /wp:paragraph --><cite><strong>Name Surname</strong><br>Role, Company</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote {"className":"is-style-quote-testimonial"} -->
<blockquote class="wp-block-quote is-style-quote-testimonial"><!-- wp:paragraph -->
<p>Short supporting text for this item. Replace it with your own copy.</p>
<!-- /wp:paragraph --><cite><strong>Name Surname</strong><br>Role, Company</cite></blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:group -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|36"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--36)">
<!-- wp:button {"className":"is-style-button-link btn-sm"} -->
<div class="wp-block-button is-style-button-link btn-sm"><a class="wp-block-button__link wp-element-button" href="/testimonials/">View More</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
