<?php
/**
 * Test the Video and Images modules.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Core\Settings;
use AK\Theme\Modules\Media\Images;
use AK\Theme\Modules\Media\Video;
use AK\Theme\Tests\TestCase;

/**
 * Class MediaTest
 *
 * @since 1.0.0
 */
class MediaTest extends TestCase {

	/**
	 * YouTube URLs and the video id each one holds.
	 *
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function youtube_urls(): array {
		return [
			'watch'        => [ 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ' ],
			'watch + list' => [ 'https://www.youtube.com/watch?list=PL1&v=dQw4w9WgXcQ', 'dQw4w9WgXcQ' ],
			'short link'   => [ 'https://youtu.be/dQw4w9WgXcQ?t=10', 'dQw4w9WgXcQ' ],
			'embed'        => [ 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ],
			'shorts'       => [ 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ],
			'live'         => [ 'https://www.youtube.com/live/dQw4w9WgXcQ', 'dQw4w9WgXcQ' ],
			'id with - _'  => [ 'https://youtu.be/a-b_c1D2e3F', 'a-b_c1D2e3F' ],
			'not a video'  => [ 'https://example.com/page', '' ],
			'empty'        => [ '', '' ],
			'null'         => [ null, '' ],
		];
	}

	/**
	 * Test the video id is extracted from every YouTube URL shape.
	 *
	 * @dataProvider youtube_urls
	 *
	 * @param mixed  $url      URL.
	 * @param string $expected Video id.
	 */
	public function test_extracts_the_video_id( $url, string $expected ): void {
		$this->assertSame( $expected, ( new Video() )->youtube_id( $url ) );
	}

	/**
	 * A yt-auto image shows the thumbnail stored on this site, never a YouTube URL.
	 */
	public function test_yt_auto_image_uses_the_local_thumbnail(): void {
		update_option(
			Video::OPTION,
			[
				'dQw4w9WgXcQ-small' => [
					'url'    => 'https://example.org/wp-content/uploads/youtube-dQw4w9WgXcQ-small.jpg',
					'width'  => 480,
					'height' => 360,
				],
			]
		);

		$html = ( new Video() )->swap_thumbnail(
			'<figure><a href="https://youtu.be/dQw4w9WgXcQ"><img src="a.jpg" srcset="x" width="1" height="1"/></a></figure>',
			[ 'attrs' => [ 'className' => 'video-item yt-auto' ] ]
		);

		$this->assertStringContainsString( 'src="https://example.org/wp-content/uploads/youtube-dQw4w9WgXcQ-small.jpg"', $html );
		$this->assertStringContainsString( 'width="480" height="360"', $html );
		$this->assertStringNotContainsString( 'srcset', $html );
		$this->assertStringNotContainsString( 'ytimg', $html );
	}

	/**
	 * Without a stored copy the editor's image stays, nothing points the browser at YouTube, and the download is
	 * queued in the background.
	 */
	public function test_missing_thumbnail_keeps_the_editor_image_and_queues_a_download(): void {
		delete_option( Video::OPTION );
		$html = '<figure><a href="https://youtu.be/dQw4w9WgXcQ"><img src="a.jpg"/></a></figure>';

		$out = ( new Video() )->swap_thumbnail( $html, [ 'attrs' => [ 'className' => 'video-main yt-auto' ] ] );

		$this->assertSame( $html, $out );
		$this->assertNotFalse( wp_next_scheduled( Video::CRON_HOOK, [ 'dQw4w9WgXcQ', 'large' ] ) );
	}

	/**
	 * The large size falls back to the standard thumbnail when YouTube only has its 120px placeholder, and the
	 * stored copy records the real size.
	 */
	public function test_fetch_falls_back_from_the_placeholder(): void {
		$responses = [
			'maxresdefault.jpg' => $this->jpeg( 120, 90 ),
			'hqdefault.jpg'     => $this->jpeg( 480, 360 ),
		];
		$this->fake_youtube( $responses );

		$this->assertTrue( ( new Video() )->fetch( 'dQw4w9WgXcQ', 'large' ) );

		$stored = get_option( Video::OPTION )['dQw4w9WgXcQ-large'];

		$this->assertSame( 480, $stored['width'] );
		$this->assertSame( 360, $stored['height'] );
		$this->assertStringStartsWith( wp_upload_dir()['baseurl'], $stored['url'] );

		wp_delete_file( wp_upload_dir()['basedir'] . substr( $stored['url'], strlen( wp_upload_dir()['baseurl'] ) ) );
	}

	/**
	 * A failed download is remembered, and not queued again for a day.
	 */
	public function test_failed_fetch_is_not_retried_at_once(): void {
		delete_option( Video::OPTION );
		$this->fake_youtube( [] );
		$video = new Video();

		$this->assertFalse( $video->fetch( 'dQw4w9WgXcQ', 'small' ) );

		$video->swap_thumbnail(
			'<figure><a href="https://youtu.be/dQw4w9WgXcQ"><img src="a.jpg"/></a></figure>',
			[ 'attrs' => [ 'className' => 'video-item yt-auto' ] ]
		);

		$this->assertFalse( wp_next_scheduled( Video::CRON_HOOK, [ 'dQw4w9WgXcQ', 'small' ] ) );
	}

	/**
	 * Saving content puts its missing thumbnails on the pending list (a page view never does); without WP-Cron, an
	 * editor's wp-admin visit downloads them (a few per page load) and takes them off the list; the next render serves
	 * the local copy.
	 */
	public function test_admin_visit_downloads_pending_thumbnails_without_cron(): void {
		delete_option( Video::OPTION );
		delete_option( Video::PENDING_OPTION );
		$this->fake_youtube( [ 'hqdefault.jpg' => $this->jpeg( 480, 360 ) ] );
		$video = new Video();
		$html  = '<figure><a href="https://youtu.be/dQw4w9WgXcQ"><img src="a.jpg"/></a></figure>';
		$block = [ 'attrs' => [ 'className' => 'video-item yt-auto' ] ];

		$video->swap_thumbnail( $html, $block );
		$this->assertSame( [], get_option( Video::PENDING_OPTION, [] ), 'A page view writes no list.' );

		$post = self::factory()->post->create_and_get(
			[ 'post_content' => '<!-- wp:image {"className":"video-item yt-auto"} -->' . $html . '<!-- /wp:image -->' ]
		);
		$video->queue_from_post( $post->ID, $post );
		$this->assertArrayHasKey( 'dQw4w9WgXcQ-small', get_option( Video::PENDING_OPTION ) );

		wp_set_current_user( 0 );
		$this->assertSame( 0, $video->fetch_pending(), 'Visitors never trigger downloads.' );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$this->assertSame( 1, $video->fetch_pending() );
		$this->assertSame( [], get_option( Video::PENDING_OPTION ) );

		$out    = $video->swap_thumbnail( $html, $block );
		$stored = get_option( Video::OPTION )['dQw4w9WgXcQ-small'];

		$this->assertStringContainsString( 'src="' . $stored['url'] . '"', $out );

		wp_delete_file( wp_upload_dir()['basedir'] . substr( $stored['url'], strlen( wp_upload_dir()['baseurl'] ) ) );
	}

	/**
	 * A YouTube Embed block keeps no player address until the visitor agrees, and loads its consent script; with the
	 * setting off, or for another provider, the block is unchanged.
	 *
	 * @since 1.3.0
	 */
	public function test_youtube_embed_waits_for_consent(): void {
		$html  = '<figure class="wp-block-embed is-type-video is-provider-youtube"><div class="wp-block-embed__wrapper"><iframe title="A" src="https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed"></iframe></div></figure>';
		$block = [
			'attrs' => [
				'url'              => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
				'providerNameSlug' => 'youtube',
			],
		];
		$video = new Video();
		$video->register_embed_script();

		$out = $video->gate_embed( $html, $block );

		$this->assertStringNotContainsString( 'youtube.com/embed', $out );
		$this->assertStringContainsString( 'data-ak-youtube="dQw4w9WgXcQ"', $out );
		$this->assertStringContainsString( 'ak-yt-embed', $out );
		$this->assertTrue( wp_script_is( Video::EMBED_HANDLE, 'enqueued' ) );

		$vimeo                              = $block;
		$vimeo['attrs']['providerNameSlug'] = 'vimeo';
		$this->assertSame( $html, $video->gate_embed( $html, $vimeo ) );

		update_option( Settings::OPTION, array_merge( Settings::defaults(), [ 'youtube_consent' => false ] ) );
		$this->assertSame( $html, $video->gate_embed( $html, $block ) );
	}

	/**
	 * Answer requests to i.ytimg.com with the given files (anything else: 404), with no network access.
	 *
	 * @param array<string, string> $files File name => JPEG bytes.
	 */
	private function fake_youtube( array $files ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, array $args, string $url ) use ( $files ) {
				unset( $pre, $args );
				$bits = $files[ basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ] ?? null;

				return [
					'headers'  => [],
					'body'     => (string) $bits,
					'response' => [
						'code'    => null === $bits ? 404 : 200,
						'message' => null === $bits ? 'Not Found' : 'OK',
					],
					'cookies'  => [],
					'filename' => null,
				];
			},
			10,
			3
		);
	}

	/**
	 * A JPEG of the given size.
	 *
	 * @param int $width  Width.
	 * @param int $height Height.
	 *
	 * @return string JPEG bytes.
	 */
	private function jpeg( int $width, int $height ): string {
		$image = imagecreatetruecolor( $width, $height );
		ob_start();
		imagejpeg( $image );

		return (string) ob_get_clean();
	}

	/**
	 * Test images without yt-auto are untouched.
	 */
	public function test_plain_image_is_untouched(): void {
		$html = '<figure><a href="https://youtu.be/dQw4w9WgXcQ"><img src="a.jpg"/></a></figure>';

		$this->assertSame( $html, ( new Video() )->swap_thumbnail( $html, [ 'attrs' => [ 'className' => 'x' ] ] ) );
	}

	/**
	 * Loading is decided by position, not by class: every page opens with ak/hero, so its images load eagerly (none
	 * gets fetchpriority: the measured LCP element is hero text, never an image); images in any later AK section load lazily;
	 * images outside AK sections are left to WordPress. A new section needs no list update.
	 */
	public function test_loading_follows_the_section(): void {
		global $wp_filter;

		// Use a fresh instance (fresh fetchpriority state), not the one the theme registered at boot.
		foreach ( [ 'render_block_data', 'render_block', 'render_block_core/image' ] as $hook ) {
			foreach ( $wp_filter[ $hook ]->callbacks ?? [] as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof Images ) {
						remove_filter( $hook, $callback['function'], $priority );
					}
				}
			}
		}
		( new Images() )->register_hooks();

		$image = static fn ( string $src, string $attrs = '', string $class_name = '' ): string => '' === $class_name
			? '<!-- wp:image --><figure class="wp-block-image"><img src="' . $src . '" alt=""' . $attrs . '/></figure><!-- /wp:image -->'
			: '<!-- wp:image {"className":"' . $class_name . '"} --><figure class="wp-block-image ' . $class_name . '"><img src="' . $src . '" alt=""' . $attrs . '/></figure><!-- /wp:image -->';
		$html  = do_blocks(
			'<!-- wp:ak/hero --><div class="hero">' . $image( 'logo.png', '', 'ac-logo' ) . $image( 'hero-1.jpg' ) . $image( 'hero-2.jpg' ) . $image( 'hero-3.jpg', ' loading="lazy"' ) . '</div><!-- /wp:ak/hero -->'
			. '<!-- wp:ak/highlights --><div>' . $image( 'section.jpg' ) . '</div><!-- /wp:ak/highlights -->'
			. $image( 'outside.jpg' )
		);

		$tag = static function ( string $src ) use ( $html ): string {
			preg_match( '#<img\b[^>]*src="' . preg_quote( $src, '#' ) . '"[^>]*>#', $html, $match );

			return $match[0] ?? '';
		};

		$this->assertStringContainsString( 'loading="eager"', $tag( 'logo.png' ) );
		$this->assertStringContainsString( 'loading="eager"', $tag( 'hero-1.jpg' ) );
		$this->assertStringContainsString( 'loading="eager"', $tag( 'hero-2.jpg' ) );
		$this->assertStringContainsString( 'loading="lazy"', $tag( 'hero-3.jpg' ), 'An explicit loading attribute is kept.' );
		$this->assertStringContainsString( 'loading="lazy"', $tag( 'section.jpg' ) );
		$this->assertStringNotContainsString( 'loading="eager"', $tag( 'outside.jpg' ) );
		$this->assertStringNotContainsString( 'fetchpriority', $html );
	}

	/**
	 * Pages that open with a plain AK Section as their hero (Blog, About, Contact, ...): that first section is on
	 * screen at load like ak/hero, so its images load eagerly (on the Blog it holds the LCP image); later sections stay lazy.
	 */
	public function test_the_opening_section_loads_eagerly_like_the_hero(): void {
		$html = $this->render_with_fresh_images(
			'<!-- wp:ak/section {"className":"bl-hero"} --><section>' . $this->image( 'blog-hero.png' ) . '<div>' . $this->image( 'avatar.png' ) . '</div></section><!-- /wp:ak/section -->'
			. '<!-- wp:ak/section {"className":"bl-list"} --><section>' . $this->image( 'card.jpg' ) . '</section><!-- /wp:ak/section -->'
			. '<!-- wp:ak/highlights --><div>' . $this->image( 'later.jpg' ) . '</div><!-- /wp:ak/highlights -->'
		);

		$this->assertStringContainsString( 'loading="eager"', $this->img_tag( $html, 'blog-hero.png' ) );
		$this->assertStringContainsString( 'loading="eager"', $this->img_tag( $html, 'avatar.png' ) );
		$this->assertStringContainsString( 'loading="lazy"', $this->img_tag( $html, 'card.jpg' ) );
		$this->assertStringContainsString( 'loading="lazy"', $this->img_tag( $html, 'later.jpg' ) );
	}

	/**
	 * Only the first section opens the page: a hero further down (unusual) doesn't make a second "opening" section,
	 * and an opening ak/hero still loads eagerly as before.
	 */
	public function test_only_the_first_section_is_the_opening_one(): void {
		$html = $this->render_with_fresh_images(
			'<!-- wp:ak/hero --><div>' . $this->image( 'hero.jpg' ) . '</div><!-- /wp:ak/hero -->'
			. '<!-- wp:ak/section --><section>' . $this->image( 'second.jpg' ) . '</section><!-- /wp:ak/section -->'
		);

		$this->assertStringContainsString( 'loading="eager"', $this->img_tag( $html, 'hero.jpg' ) );
		$this->assertStringContainsString( 'loading="lazy"', $this->img_tag( $html, 'second.jpg' ) );
	}

	/**
	 * Render blocks with a fresh Images instance (fresh per-page state), not the one the theme registered at boot.
	 *
	 * @param string $blocks Block markup.
	 *
	 * @return string Rendered HTML.
	 */
	private function render_with_fresh_images( string $blocks ): string {
		global $wp_filter;

		foreach ( [ 'render_block_data', 'render_block', 'render_block_core/image' ] as $hook ) {
			foreach ( $wp_filter[ $hook ]->callbacks ?? [] as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof Images ) {
						remove_filter( $hook, $callback['function'], $priority );
					}
				}
			}
		}
		( new Images() )->register_hooks();

		return do_blocks( $blocks );
	}

	/**
	 * A core Image block.
	 *
	 * @param string $src Image address.
	 *
	 * @return string Block markup.
	 */
	private function image( string $src ): string {
		return '<!-- wp:image --><figure class="wp-block-image"><img src="' . $src . '" alt=""/></figure><!-- /wp:image -->';
	}

	/**
	 * The rendered <img> tag for an address.
	 *
	 * @param string $html Rendered HTML.
	 * @param string $src  Image address.
	 *
	 * @return string The tag, or ''.
	 */
	private function img_tag( string $html, string $src ): string {
		preg_match( '#<img\b[^>]*src="' . preg_quote( $src, '#' ) . '"[^>]*>#', $html, $match );

		return $match[0] ?? '';
	}
}
