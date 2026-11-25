<?php
/**
 * Title: Blog: Call-out card
 * Slug: aktheme/blog-program-cta
 * Description: A call-out inside an article: title, short text and a button on a light blue panel beside a photo.
 * Categories: ak-sections
 * Keywords: ak, blog, article, cta
 * Viewport Width: 800
 *
 * @package AK\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"className":"bp-cta-card","layout":{"type":"default"}} -->
<div class="wp-block-group bp-cta-card"><!-- wp:group {"className":"bp-cta-text","layout":{"type":"default"}} -->
<div class="wp-block-group bp-cta-text"><!-- wp:paragraph {"className":"bp-cta-title"} -->
<p class="bp-cta-title"><?php esc_html_e( 'Your service or offer', 'aktheme' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><?php esc_html_e( 'One or two lines on what the reader gets and why it matters.', 'aktheme' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"btn-navy btn-sm"} -->
<div class="wp-block-button btn-navy btn-sm"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Learn More', 'aktheme' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"bp-cta-img"} -->
<figure class="wp-block-image size-full bp-cta-img"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/theme/photo-placeholder.webp' ) ); ?>" alt="<?php echo esc_attr__( 'Placeholder image', 'aktheme' ); ?>" width="584" height="700"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->
