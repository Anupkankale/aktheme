<?php
/**
 * Title: Video section
 * Slug: aktheme/video-section
 * Description: A large framed video with a list of thumbnails beside it.
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

<!-- wp:group {"className":"is-style-group-video-gallery","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-group-video-gallery">
<!-- wp:embed {"url":"https://www.youtube.com/watch?v=aqz-KE-bpKQ","type":"video","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio is-style-embed-video-frame"} -->
<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio is-style-embed-video-frame"><div class="wp-block-embed__wrapper">
https://www.youtube.com/watch?v=aqz-KE-bpKQ
</div></figure>
<!-- /wp:embed -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|12"}},"layout":{"type":"default"}} -->
<div class="wp-block-group">
<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-image-video-thumb"} -->
<figure class="wp-block-image size-large is-style-image-video-thumb"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/video-placeholder.svg' ) ); ?>" alt="Placeholder image"/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-image-video-thumb"} -->
<figure class="wp-block-image size-large is-style-image-video-thumb"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/video-placeholder.svg' ) ); ?>" alt="Placeholder image"/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-image-video-thumb"} -->
<figure class="wp-block-image size-large is-style-image-video-thumb"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/video-placeholder.svg' ) ); ?>" alt="Placeholder image"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
