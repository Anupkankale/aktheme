<?php
/**
 * Title: Header
 * Slug: aktheme/header
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * The markup of parts/header.html. A pattern (PHP) instead of plain HTML so links follow the site address and images
 * the theme folder: a root-relative "/contact/" or "/wp-content/themes/..." breaks on a site in a sub-directory, a
 * renamed theme folder or a custom content directory.
 *
 * @package AK\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:template-part {"slug":"promo-bar","tagName":"div"} /-->

<!-- wp:group {"align":"full","className":"ak-pill-wrap","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull ak-pill-wrap">
	<!-- wp:group {"className":"ak-pill","style":{"border":{"radius":"var(--wp--preset--border-radius--22)"},"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"white","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group ak-pill has-white-background-color has-background" style="border-radius:var(--wp--preset--border-radius--22);padding-top:0;padding-right:var(--wp--preset--spacing--50);padding-bottom:0;padding-left:var(--wp--preset--spacing--50)">
		<!-- wp:site-logo {"shouldSyncIcon":false} /-->

		<!-- wp:navigation {"overlayMenu":"mobile","icon":"menu","className":"ak-nav ak-menu-primary","layout":{"type":"flex","justifyContent":"left","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"0"}}} -->
			<!-- wp:navigation-submenu {"label":"Services","url":"<?php echo esc_url( home_url( '/services/' ) ); ?>","kind":"custom"} -->
				<!-- wp:navigation-link {"label":"Service One","url":"<?php echo esc_url( home_url( '/services/service-one/' ) ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Service Two","url":"<?php echo esc_url( home_url( '/services/service-two/' ) ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Service Three","url":"<?php echo esc_url( home_url( '/services/service-three/' ) ); ?>","kind":"custom"} /-->
			<!-- /wp:navigation-submenu -->
			<!-- wp:navigation-submenu {"label":"Company"} -->
				<!-- wp:navigation-link {"label":"About Us","url":"<?php echo esc_url( home_url( '/about-us/' ) ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Case Studies","url":"<?php echo esc_url( home_url( '/case-studies/' ) ); ?>","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Testimonials","url":"<?php echo esc_url( home_url( '/testimonials/' ) ); ?>","kind":"custom"} /-->
			<!-- /wp:navigation-submenu -->
			<!-- wp:navigation-link {"label":"Blog","url":"<?php echo esc_url( home_url( '/blog/' ) ); ?>","kind":"custom"} /-->
			<!-- wp:navigation-link {"label":"Contact Us","url":"<?php echo esc_url( home_url( '/contact/' ) ); ?>","kind":"custom","className":"ak-nav-contact"} /-->
		<!-- /wp:navigation -->

		<!-- wp:buttons {"className":"ak-pill-cta"} -->
		<div class="wp-block-buttons ak-pill-cta">
			<!-- wp:button {"backgroundColor":"navy-footer","style":{"border":{"radius":"var(--wp--preset--border-radius--5)"}}} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-navy-footer-background-color has-background wp-element-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" style="border-radius:var(--wp--preset--border-radius--5)">Contact Us</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
