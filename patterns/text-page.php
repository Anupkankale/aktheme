<?php
/**
 * Title: Text Page
 * Slug: aktheme/text-page
 * Description: A page of text (policy, about, contact, resources): the navy-to-gold hero with a breadcrumb, the title and a lead, then the text on a white card (styled like a blog article: headings, lists, tables, questions) beside the sticky advisor form.
 * Categories: ak-pages
 * Keywords: ak, page, text, policy, privacy, contact, resources
 * Block Types: core/post-content
 * Viewport Width: 1440
 *
 * @package AK\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:ak/section {"className":"ac-hero tp-hero"} -->
<section class="wp-block-ak-section alignfull ak-section section ak-blank ac-hero tp-hero"><!-- wp:group {"className":"ak-container ac-hero-text","layout":{"type":"default"}} -->
<div class="wp-block-group ak-container ac-hero-text"><!-- wp:paragraph {"className":"ac-crumbs"} -->
<p class="ac-crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'aktheme' ); ?></a> » <?php esc_html_e( 'Page Title', 'aktheme' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading"><?php esc_html_e( 'Page Title', 'aktheme' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"ac-lead"} -->
<p class="ac-lead"><?php esc_html_e( 'One line about this page.', 'aktheme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></section>
<!-- /wp:ak/section -->
<!-- wp:ak/section {"className":"bl-main tp-main"} -->
<section class="wp-block-ak-section alignfull ak-section section ak-blank bl-main tp-main"><!-- wp:group {"className":"ak-container bl-layout","layout":{"type":"default"}} -->
<div class="wp-block-group ak-container bl-layout"><!-- wp:group {"className":"tp-body bp-content","layout":{"type":"default"}} -->
<div class="wp-block-group tp-body bp-content"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'A section heading', 'aktheme' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php esc_html_e( 'The page text: paragraphs, lists, tables and questions, styled like a blog article.', 'aktheme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<!-- wp:group {"className":"bl-aside","layout":{"type":"default"}} -->
<div class="wp-block-group bl-aside"><!-- wp:group {"anchor":"advisor-form","className":"bl-form","layout":{"type":"default"}} -->
<div id="advisor-form" class="wp-block-group bl-form"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Talk to an Advisor', 'aktheme' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:shortcode -->
[aktheme_advisor_form]
<!-- /wp:shortcode --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:ak/section -->
