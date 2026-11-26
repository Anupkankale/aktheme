<?php
/**
 * Title: Footer
 * Slug: aktheme/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * The markup of parts/footer.html. A pattern (PHP) instead of plain HTML so links follow the site address and images
 * the theme folder: a root-relative "/contact/" or "/wp-content/themes/..." breaks on a site in a sub-directory, a
 * renamed theme folder or a custom content directory.
 *
 * @package AK\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"align":"full","className":"ak-footer","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}},"backgroundColor":"navy-night","textColor":"white","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull ak-footer has-white-color has-navy-night-background-color has-text-color has-background" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
<!-- wp:group {"className":"ak-footer-inner","layout":{"type":"constrained","contentSize":"var(--wp--style--global--wide-size)"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-inner">
<!-- wp:buttons {"className":"ak-footer-tabs","layout":{"type":"flex","justifyContent":"left"}} -->
<div class="wp-block-buttons ak-footer-tabs">
<!-- wp:button {"className":"is-style-outline ak-footer-tab-accent"} -->
<div class="wp-block-button is-style-outline ak-footer-tab-accent"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">About Us</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/services/' ) ); ?>">Services</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/case-studies/' ) ); ?>">Case Studies</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/testimonials/' ) ); ?>">Testimonials</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get in Touch</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:group {"className":"ak-footer-main","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-main">
<!-- wp:group {"className":"ak-footer-about-col","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-about-col">
<!-- wp:group {"className":"ak-footer-logo","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-logo">
<!-- wp:site-logo {"shouldSyncIcon":false} /-->
</div>
<!-- /wp:group -->

<!-- wp:heading {"level":2,"className":"ak-footer-brand"} -->
<h2 class="wp-block-heading ak-footer-brand">Your Company</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ak-footer-about"} -->
<p class="ak-footer-about">A short line about the company: who you are, who you help and what makes you different. Edit it in Appearance → Editor → Patterns → Footer.</p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"ak-footer-bullets"} -->
<ul class="wp-block-list ak-footer-bullets">
<!-- wp:list-item -->
<li>Key benefit one</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Key benefit two</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Key benefit three</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Key benefit four</li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:group {"className":"ak-footer-follow","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group ak-footer-follow">
<!-- wp:paragraph {"className":"ak-footer-follow-label"} -->
<p class="ak-footer-follow-label">Follow Us</p>
<!-- /wp:paragraph -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"ak-footer-linkedin"} -->
<figure class="wp-block-image size-full ak-footer-linkedin"><a href="https://www.linkedin.com/" target="_blank" rel="noreferrer noopener"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/theme/linkedin-logo.svg' ) ); ?>" alt="Your Company on LinkedIn"/></a></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ak-footer-refer","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-refer">
<!-- wp:paragraph -->
<p>Ready to start? Tell us about your project and we will get back to you within one working day.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"navy-night"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-navy-night-color has-white-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact Us</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ak-footer-right","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-right">
<!-- wp:paragraph {"className":"ak-footer-mobile-label ak-footer-quick"} -->
<p class="ak-footer-mobile-label ak-footer-quick">Quick Links</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"ak-footer-links","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-links">
<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group">
<!-- wp:details {"showContent":true,"className":"ak-footer-acc ak-footer-acc-main"} -->
<details class="wp-block-details ak-footer-acc ak-footer-acc-main" open><summary>Company</summary>
<!-- wp:list {"className":"ak-footer-list ak-menu-footer-main"} -->
<ul class="wp-block-list ak-footer-list ak-menu-footer-main">
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">About Us</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact Us</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/sitemap_index.xml' ) ); ?>">Sitemap</a></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
</details>
<!-- /wp:details -->
</div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group">
<!-- wp:details {"showContent":true,"className":"ak-footer-acc ak-footer-acc-col-2"} -->
<details class="wp-block-details ak-footer-acc ak-footer-acc-col-2" open><summary>Column Two</summary>
<!-- wp:list {"className":"ak-footer-list ak-menu-footer-col-2"} -->
<ul class="wp-block-list ak-footer-list ak-menu-footer-col-2">
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Link One</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Link Two</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Link Three</a></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
</details>
<!-- /wp:details -->

<!-- wp:details {"showContent":true,"className":"ak-footer-acc ak-footer-acc-col-3"} -->
<details class="wp-block-details ak-footer-acc ak-footer-acc-col-3" open><summary>Column Three</summary>
<!-- wp:list {"className":"ak-footer-list ak-menu-footer-col-3"} -->
<ul class="wp-block-list ak-footer-list ak-menu-footer-col-3">
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Link One</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Link Two</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Link Three</a></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
</details>
<!-- /wp:details -->
</div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group">
<!-- wp:details {"showContent":true,"className":"ak-footer-acc ak-footer-acc-col-4"} -->
<details class="wp-block-details ak-footer-acc ak-footer-acc-col-4" open><summary>Services <span class="ak-footer-badge">Popular</span></summary>
<!-- wp:list {"className":"ak-footer-list ak-footer-list-col-4 ak-menu-footer-col-4"} -->
<ul class="wp-block-list ak-footer-list ak-footer-list-col-4 ak-menu-footer-col-4">
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/services/service-one/' ) ); ?>">Service One</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/services/service-two/' ) ); ?>">Service Two</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/services/service-three/' ) ); ?>">Service Three</a></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
</details>
<!-- /wp:details -->

<!-- wp:group {"className":"ak-footer-news","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-news">
<!-- wp:heading {"level":3,"className":"ak-footer-news-title"} -->
<h3 class="wp-block-heading ak-footer-news-title">Newsletter</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ak-footer-news-text"} -->
<p class="ak-footer-news-text">News and updates, once a month.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[contact-form-7 title="Newsletter"]
<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"ak-footer-mobile-label ak-footer-presence"} -->
<p class="ak-footer-mobile-label ak-footer-presence">Our Presence</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"ak-footer-offices","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-offices">
<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"ak-footer-map"} -->
<figure class="wp-block-image size-full ak-footer-map"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/theme/world-map.svg' ) ); ?>" alt="" loading="lazy"/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"ak-footer-office ak-footer-office-us","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-office ak-footer-office-us">
<!-- wp:heading {"level":3,"className":"ak-footer-h"} -->
<h3 class="wp-block-heading ak-footer-h">Head Office</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ak-footer-address"} -->
<p class="ak-footer-address">Street address, City, Country<br><a href="tel:+10000000000">+1 000 000 0000</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ak-footer-office ak-footer-office-gb","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-office ak-footer-office-gb">
<!-- wp:heading {"level":3,"className":"ak-footer-h"} -->
<h3 class="wp-block-heading ak-footer-h">Branch Office</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ak-footer-address"} -->
<p class="ak-footer-address">Street address, City, Country<br><a href="tel:+10000000000">+1 000 000 0000</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->


</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ak-footer-countries","layout":{"type":"default"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-countries">
<!-- wp:details {"showContent":true,"className":"ak-footer-acc ak-footer-acc-regions"} -->
<details class="wp-block-details ak-footer-acc ak-footer-acc-regions" open><summary>Locations</summary>
<!-- wp:heading {"level":3,"className":"ak-footer-country-h"} -->
<h3 class="wp-block-heading ak-footer-country-h">Region 1</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"ak-footer-country-list ak-menu-footer-region-1"} -->
<ul class="wp-block-list ak-footer-country-list ak-menu-footer-region-1">
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Location One</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Location Two</a></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3,"className":"ak-footer-country-h"} -->
<h3 class="wp-block-heading ak-footer-country-h">Region 2</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"ak-footer-country-list ak-menu-footer-region-2"} -->
<ul class="wp-block-list ak-footer-country-list ak-menu-footer-region-2">
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Location One</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Location Two</a></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3,"className":"ak-footer-country-h"} -->
<h3 class="wp-block-heading ak-footer-country-h">Region 3</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"ak-footer-country-list ak-menu-footer-region-3"} -->
<ul class="wp-block-list ak-footer-country-list ak-menu-footer-region-3">
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Location One</a></li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Location Two</a></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->



</details>
<!-- /wp:details -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ak-footer-bottom","backgroundColor":"white","textColor":"ink","layout":{"type":"constrained","contentSize":"var(--wp--style--global--wide-size)"},"style":{"spacing":{"blockGap":"0"}}} -->
<div class="wp-block-group ak-footer-bottom has-ink-color has-white-background-color has-text-color has-background">
<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:paragraph {"className":"ak-footer-copy"} -->
<p class="ak-footer-copy">© [aktheme_year] Your Company. All rights reserved.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
