<?php
/**
 * Title: Blog: Contact banner
 * Slug: aktheme/blog-skills-banner
 * Description: A blue banner inside an article: a title, two short points and a Contact Us button (goes to the advisor form in the sidebar).
 * Categories: ak-sections
 * Keywords: ak, blog, article, cta, contact, banner
 * Viewport Width: 800
 *
 * @package AK\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"className":"bp-banner","layout":{"type":"default"}} -->
<div class="wp-block-group bp-banner"><!-- wp:paragraph {"className":"bp-banner-title"} -->
<p class="bp-banner-title"><?php esc_html_e( 'Need help with this?', 'aktheme' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li><?php esc_html_e( 'First short benefit', 'aktheme' ); ?></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><?php esc_html_e( 'Second short benefit', 'aktheme' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"btn-white btn-sm"} -->
<div class="wp-block-button btn-white btn-sm"><a class="wp-block-button__link wp-element-button" href="#advisor-form"><?php esc_html_e( 'Contact Us', 'aktheme' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
