<?php
/**
 * YouTube thumbnails for the video gallery, served from this site.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Media;

use AK\Theme\Core\Settings;
use WP_HTML_Tag_Processor;
use WP_Post;
use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;

/**
 * Class Video
 *
 * An Image block with the class "yt-auto" that links to a YouTube video shows that video's thumbnail, so editors only
 * paste the link (AK Video gallery). Without a YouTube link, or without "yt-auto", the image chosen in the editor is
 * shown.
 *
 * Visitors' browsers never contact YouTube for a thumbnail (privacy, GDPR): the thumbnail is downloaded once into the
 * uploads folder and served from there. Until that copy exists the editor's image shows. Saving a post (or a synced
 * pattern, template or template part) puts its missing thumbnails on a pending list that two things work through, so it
 * does not depend on WP-Cron alone (hosts often set DISABLE_WP_CRON): a WP-Cron event, and any wp-admin page load by
 * someone who can edit posts (a few per request, never during Ajax or Heartbeat requests). A page view only schedules
 * the cron event, once per video; it never writes the lists. The video itself only loads from youtube-nocookie.com once
 * the visitor presses play (alumni-video view.js).
 *
 * Embed blocks with a YouTube video get the same consent notice (setting `youtube_consent`, on by default): the player
 * iframe keeps no `src` until the visitor agrees, and then loads from youtube-nocookie.com (youtube-embed.js).
 *
 * @since 1.0.0
 */
class Video implements Registrable {

	/**
	 * Option holding the downloaded thumbnails: "<video id>-<size>" => url, width, height (or a failed-at time).
	 */
	public const OPTION = 'aktheme_youtube_thumbnails';

	/**
	 * Cron hook that downloads one thumbnail.
	 */
	public const CRON_HOOK = 'aktheme_fetch_youtube_thumbnail';

	/**
	 * Handle of the script that loads a YouTube Embed block after consent.
	 */
	public const EMBED_HANDLE = 'ak-youtube-embed';

	/**
	 * Option holding the thumbnails still to download: "<video id>-<size>" => [ id, size ].
	 */
	public const PENDING_OPTION = 'aktheme_youtube_thumbnails_pending';

	/**
	 * Thumbnails downloaded per wp-admin page load, so the admin never slows down much.
	 */
	private const ADMIN_BATCH = 3;

	/**
	 * Seconds before a failed download is tried again.
	 */
	private const RETRY_AFTER = DAY_IN_SECONDS;

	/**
	 * Largest thumbnail accepted, in bytes.
	 */
	private const MAX_BYTES = 2 * MB_IN_BYTES;

	/**
	 * Width YouTube serves in place of a missing maxresdefault.jpg (a grey placeholder with a 404 or 200 status).
	 */
	private const PLACEHOLDER_WIDTH = 120;

	/**
	 * Register hooks.
	 *
	 * @since 1.0.0
	 */
	public function register_hooks(): void {
		add_filter( 'render_block_core/image', [ $this, 'swap_thumbnail' ], 10, 2 );
		add_filter( 'render_block_core/embed', [ $this, 'gate_embed' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_embed_script' ] );
		add_action( self::CRON_HOOK, [ $this, 'run_fetch' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'run_pending' ] );
		add_action( 'wp_after_insert_post', [ $this, 'queue_from_post' ], 10, 2 );
	}

	/**
	 * Register the script that loads a consented YouTube Embed block (enqueued by gate_embed() where one renders).
	 *
	 * @since 1.3.0
	 */
	public function register_embed_script(): void {
		// A function call (not a constant path) because this is build output that is not in the repo.
		$asset_file = get_theme_file_path( 'assets/build/js/frontend/youtube-embed.asset.php' );
		$asset      = is_readable( $asset_file ) ? require $asset_file : []; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Built file.
		$src        = get_theme_file_uri( 'assets/build/js/frontend/youtube-embed.js' );

		if ( '' === $src ) {
			return;
		}

		wp_register_script(
			self::EMBED_HANDLE,
			$src,
			(array) ( $asset['dependencies'] ?? [ 'wp-i18n' ] ),
			(string) ( $asset['version'] ?? AKTHEME_VERSION ),
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
		wp_set_script_translations( self::EMBED_HANDLE, 'aktheme', AKTHEME_PATH . '/languages' );
	}

	/**
	 * Keep a YouTube Embed block's player from loading until the visitor agrees: the iframe loses its `src` (kept as the
	 * video ID in `data-ak-youtube`) and the script shows the consent notice over it.
	 *
	 * @since 1.3.0
	 *
	 * @param string               $html  Block markup.
	 * @param array<string, mixed> $block Parsed block.
	 *
	 * @return string Block markup.
	 */
	public function gate_embed( string $html, array $block ): string {
		$provider = (string) ( $block['attrs']['providerNameSlug'] ?? '' );
		$id       = $this->youtube_id( (string) ( $block['attrs']['url'] ?? '' ) );

		if ( 'youtube' !== $provider || '' === $id || ! Settings::get( 'youtube_consent' ) ) {
			return $html;
		}

		$tags = new WP_HTML_Tag_Processor( $html );

		if ( ! $tags->next_tag( [ 'class_name' => 'wp-block-embed' ] ) ) {
			return $html;
		}

		$tags->add_class( 'ak-yt-embed' );

		if ( ! $tags->next_tag( [ 'tag_name' => 'iframe' ] ) ) {
			return $html;
		}

		$tags->remove_attribute( 'src' );
		$tags->set_attribute( 'data-ak-youtube', $id );

		wp_enqueue_script( self::EMBED_HANDLE );

		return $tags->get_updated_html();
	}

	/**
	 * Put the missing thumbnails of a saved post, synced pattern, template or template part on the pending list.
	 *
	 * @since 1.3.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public function queue_from_post( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || ! str_contains( $post->post_content, 'yt-auto' ) ) {
			return;
		}

		foreach ( $this->videos_in( parse_blocks( $post->post_content ) ) as [ $id, $size ] ) {
			if ( null === $this->stored( $id, $size ) ) {
				$this->schedule( $id, $size, true );
			}
		}
	}

	/**
	 * The videos of the "yt-auto" Image blocks among some blocks and their inner blocks.
	 *
	 * @since 1.3.0
	 *
	 * @param array<int|string, array<string, mixed>> $blocks Parsed blocks.
	 *
	 * @return list<array{0: string, 1: string}> [ video id, size ] pairs.
	 */
	private function videos_in( array $blocks ): array {
		$videos = [];

		foreach ( $blocks as $block ) {
			$class = (string) ( $block['attrs']['className'] ?? '' );

			if ( 'core/image' === ( $block['blockName'] ?? '' ) && preg_match( '/(^|\s)yt-auto(\s|$)/', $class ) && preg_match( '#<a\b[^>]*\bhref="([^"]+)"#', (string) ( $block['innerHTML'] ?? '' ), $link ) ) {
				$id = $this->youtube_id( html_entity_decode( $link[1] ) );

				if ( '' !== $id ) {
					$videos[] = [ $id, preg_match( '/(^|\s)video-main(\s|$)/', $class ) ? 'large' : 'small' ];
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$videos = array_merge( $videos, $this->videos_in( $block['innerBlocks'] ) );
			}
		}

		return $videos;
	}

	/**
	 * The admin_init callback: download a few pending thumbnails (actions return nothing).
	 *
	 * @since 1.2.1
	 */
	public function run_pending(): void {
		$this->fetch_pending();
	}

	/**
	 * Download a few pending thumbnails while an editor uses wp-admin, for sites where WP-Cron does not run.
	 *
	 * @since 1.2.1
	 *
	 * @return int Number of thumbnails downloaded.
	 */
	public function fetch_pending(): int {
		// Ajax and Heartbeat requests also fire admin_init; a slow download there would hold up the editor.
		if ( wp_doing_ajax() || ! current_user_can( 'edit_posts' ) ) {
			return 0;
		}

		$fetched = 0;

		foreach ( array_slice( $this->pending(), 0, self::ADMIN_BATCH ) as [ $id, $size ] ) {
			$fetched += $this->fetch( (string) $id, (string) $size ) ? 1 : 0;
		}

		return $fetched;
	}

	/**
	 * The 11-character video ID from any YouTube URL (watch, youtu.be, embed, shorts, live).
	 *
	 * @param mixed $url URL.
	 *
	 * @return string Video id, or an empty string.
	 */
	public function youtube_id( $url ): string {
		return 1 === preg_match( '#(?:youtu\.be/|[?&]v=|/embed/|/shorts/|/live/)([\w-]{11})#', (string) $url, $match ) ? $match[1] : '';
	}

	/**
	 * Swap the image of a "yt-auto" block for the video's locally stored thumbnail.
	 *
	 * @param string               $html  Block markup.
	 * @param array<string, mixed> $block Parsed block.
	 *
	 * @return string Block markup.
	 */
	public function swap_thumbnail( string $html, array $block ): string {
		$class = (string) ( $block['attrs']['className'] ?? '' );

		if ( ! preg_match( '/(^|\s)yt-auto(\s|$)/', $class ) || ! preg_match( '#<a\b[^>]*\bhref="([^"]+)"#', $html, $link ) ) {
			return $html;
		}

		$id = $this->youtube_id( html_entity_decode( $link[1] ) );

		if ( '' === $id ) {
			return $html;
		}

		// Main player: the largest thumbnail (not every video has one, fetch() falls back to the standard one).
		$size  = 1 === preg_match( '/(^|\s)video-main(\s|$)/', $class ) ? 'large' : 'small';
		$thumb = $this->stored( $id, $size );

		if ( null === $thumb ) {
			$this->schedule( $id, $size ); // Cron only: a page view never writes the thumbnail lists.

			return $html; // The editor's image until the local copy exists.
		}

		return (string) preg_replace_callback(
			'#<img\b[^>]*>#',
			static function ( array $img ) use ( $thumb ): string {
				$tag = (string) preg_replace( '#\s(?:srcset|sizes|width|height)="[^"]*"#', '', $img[0] );
				$tag = (string) preg_replace( '#\ssrc="[^"]*"#', ' src="' . esc_url( $thumb['url'] ) . '"', $tag );

				// The thumbnail's real size, so the browser reserves the space before it loads.
				return str_replace( '<img', '<img width="' . (int) $thumb['width'] . '" height="' . (int) $thumb['height'] . '"', $tag );
			},
			$html,
			1
		);
	}

	/**
	 * The cron callback: download one thumbnail (actions return nothing).
	 *
	 * @since 1.2.1
	 *
	 * @param string $id   Video id.
	 * @param string $size `large` or `small`.
	 */
	public function run_fetch( string $id, string $size ): void {
		$this->fetch( $id, $size );
	}

	/**
	 * Download one thumbnail into the uploads folder and remember it.
	 *
	 * The large size tries maxresdefault.jpg and falls back to hqdefault.jpg when YouTube has none (a 404, or its
	 * 120px placeholder). A failure is remembered and retried after a day.
	 *
	 * @since 1.2.1
	 *
	 * @param string $id   Video id.
	 * @param string $size `large` or `small`.
	 *
	 * @return bool Whether a thumbnail is now stored.
	 */
	public function fetch( string $id, string $size ): bool {
		if ( 1 !== preg_match( '/^[\w-]{11}$/', $id ) || ! in_array( $size, [ 'large', 'small' ], true ) ) {
			return false;
		}

		$files = 'large' === $size ? [ 'maxresdefault.jpg', 'hqdefault.jpg' ] : [ 'hqdefault.jpg' ];
		$image = null;

		foreach ( $files as $file ) {
			$image = $this->download( 'https://i.ytimg.com/vi/' . $id . '/' . $file );

			if ( null !== $image ) {
				break;
			}
		}

		$thumbnails = $this->all();
		$key        = $id . '-' . $size;

		$this->unqueue( $key );

		if ( null === $image ) {
			$thumbnails[ $key ] = [ 'failed' => time() ];
			update_option( self::OPTION, $thumbnails, false );

			return false;
		}

		$upload = wp_upload_bits( 'youtube-' . $key . '.jpg', null, $image['bits'] );

		if ( ! empty( $upload['error'] ) || empty( $upload['url'] ) ) {
			$thumbnails[ $key ] = [ 'failed' => time() ];
			update_option( self::OPTION, $thumbnails, false );

			return false;
		}

		$thumbnails[ $key ] = [
			'url'    => (string) $upload['url'],
			'width'  => $image['width'],
			'height' => $image['height'],
		];
		update_option( self::OPTION, $thumbnails, false );

		return true;
	}

	/**
	 * A stored thumbnail.
	 *
	 * @param string $id   Video id.
	 * @param string $size `large` or `small`.
	 *
	 * @return array{url: string, width: int, height: int}|null The thumbnail, or null when there is none yet.
	 */
	private function stored( string $id, string $size ): ?array {
		$thumb = $this->all()[ $id . '-' . $size ] ?? null;

		if ( ! is_array( $thumb ) || empty( $thumb['url'] ) ) {
			return null;
		}

		return [
			'url'    => (string) $thumb['url'],
			'width'  => (int) ( $thumb['width'] ?? 0 ),
			'height' => (int) ( $thumb['height'] ?? 0 ),
		];
	}

	/**
	 * Schedule the download of a thumbnail, unless it is scheduled already or failed less than a day ago, and (when
	 * saving content) put it on the pending list for sites without WP-Cron.
	 *
	 * @param string $id    Video id.
	 * @param string $size  `large` or `small`.
	 * @param bool   $queue Also add it to the pending list.
	 */
	private function schedule( string $id, string $size, bool $queue = false ): void {
		$key    = $id . '-' . $size;
		$failed = (int) ( $this->all()[ $key ]['failed'] ?? 0 );

		if ( $failed > time() - self::RETRY_AFTER ) {
			return;
		}

		$pending = $queue ? $this->pending() : [];

		if ( $queue && ! isset( $pending[ $key ] ) ) {
			$pending[ $key ] = [ $id, $size ];
			update_option( self::PENDING_OPTION, $pending, false );
		}

		if ( false === wp_next_scheduled( self::CRON_HOOK, [ $id, $size ] ) ) {
			wp_schedule_single_event( time(), self::CRON_HOOK, [ $id, $size ] );
		}
	}

	/**
	 * Thumbnails still to download.
	 *
	 * @return array<string, array{0: string, 1: string}> Key => [ id, size ].
	 */
	private function pending(): array {
		$pending = get_option( self::PENDING_OPTION, [] );

		return is_array( $pending ) ? $pending : [];
	}

	/**
	 * Take a thumbnail off the pending list.
	 *
	 * @param string $key "<video id>-<size>".
	 */
	private function unqueue( string $key ): void {
		$pending = $this->pending();

		if ( isset( $pending[ $key ] ) ) {
			unset( $pending[ $key ] );
			update_option( self::PENDING_OPTION, $pending, false );
		}
	}

	/**
	 * Every stored thumbnail entry.
	 *
	 * @return array<string, array<string, mixed>> Key => entry.
	 */
	private function all(): array {
		$thumbnails = get_option( self::OPTION, [] );

		return is_array( $thumbnails ) ? $thumbnails : [];
	}

	/**
	 * Fetch one JPEG from YouTube's image server.
	 *
	 * @param string $url Thumbnail URL.
	 *
	 * @return array{bits: string, width: int, height: int}|null The image, or null when missing, a placeholder or invalid.
	 */
	private function download( string $url ): ?array {
		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'             => 3, // VIP standard; this runs in a background cron job, never during a page view.
				'limit_response_size' => self::MAX_BYTES,
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$bits = (string) wp_remote_retrieve_body( $response );
		$info = '' === $bits ? false : getimagesizefromstring( $bits );

		if ( false === $info || IMAGETYPE_JPEG !== $info[2] || $info[0] <= self::PLACEHOLDER_WIDTH ) {
			return null;
		}

		return [
			'bits'   => $bits,
			'width'  => (int) $info[0],
			'height' => (int) $info[1],
		];
	}
}
