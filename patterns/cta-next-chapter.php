<?php
/**
 * Title: CTA: Next Step
 * Slug: aktheme/cta-next-chapter
 * Description: The closing call to action with the graduates photo. The source of the synced pattern "CTA: Next Step" (Patterns > My patterns), which the theme creates once; edit that one and it changes on every page. Hidden from the inserter so no unsynced copy is added by mistake.
 * Categories: ak-sections
 * Keywords: ak, section, shared
 * Inserter: no
 * Viewport Width: 1440
 *
 * @package AK\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:ak/cta {"align":"full"} -->
<section class="wp-block-ak-cta alignfull ak-section cta-wrap"><!-- wp:group {"className":"ak-container","layout":{"type":"default"}} -->
<div class="wp-block-group ak-container"><!-- wp:group {"className":"cta","layout":{"type":"default"}} -->
<div class="wp-block-group cta"><!-- wp:group {"className":"cta-text","layout":{"type":"default"}} -->
<div class="wp-block-group cta-text"><!-- wp:heading -->
<h2 class="wp-block-heading">Section <strong>Heading</strong></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"cta-sub"} -->
<p class="cta-sub">Short supporting text for this item. Replace it with your own copy.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"btn-white btn-lg"} -->
<div class="wp-block-button btn-white btn-lg"><a class="wp-block-button__link wp-element-button" href="#advisor-form">Learn More</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"cta-photo"} -->
<figure class="wp-block-image size-full cta-photo"><img src="<?php echo esc_url( get_theme_file_uri( "assets/images/theme/photo-placeholder.webp" ) ); ?>" alt="Placeholder image"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:ak/cta -->
