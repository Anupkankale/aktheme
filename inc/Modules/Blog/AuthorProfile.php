<?php
/**
 * Author profile fields for the blog's author box.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Blog;

use WP_Block;
use WP_Comment;
use WP_Post;
use WP_User;
use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;

/**
 * Class AuthorProfile
 *
 * Adds two fields to Users > Profile for the author box under a blog article (templates/single.html):
 * - "Job title": shown under the author's name.
 * - "Author photo": an image from this site's Media Library (paste its address: Media > Library > the image > Copy URL),
 *   used instead of the Gravatar. It is stored as the attachment ID, so only images on this site can be used (no
 *   third-party tracking pixels in comment avatars), and only for users who can upload files.
 * The bio is WordPress's own "Biographical Info" and the LinkedIn link the "Website" field (see SinglePost).
 *
 * @since 1.1.21
 */
class AuthorProfile implements Registrable {

	/**
	 * User meta: job title.
	 *
	 * @since 1.1.21
	 * @var string
	 */
	public const JOB_TITLE = 'aktheme_job_title';

	/**
	 * User meta: author photo address. Read only for photos saved before 1.3.0 (see PHOTO_ID).
	 *
	 * @since 1.1.21
	 * @var string
	 */
	public const PHOTO = 'aktheme_author_photo';

	/**
	 * User meta: author photo attachment ID.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	public const PHOTO_ID = 'aktheme_author_photo_id';

	/**
	 * Register hooks.
	 *
	 * @since 1.1.21
	 */
	public function register_hooks(): void {
		add_action( 'show_user_profile', [ $this, 'fields' ] );
		add_action( 'edit_user_profile', [ $this, 'fields' ] );
		add_action( 'personal_options_update', [ $this, 'save' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save' ] );
		add_filter( 'pre_get_avatar_data', [ $this, 'avatar' ], 10, 2 );
		add_filter( 'render_block_core/post-author', [ $this, 'job_title' ], 10, 3 );
	}

	/**
	 * Print the fields on the profile screen.
	 *
	 * @since 1.1.21
	 *
	 * @param WP_User $user The user being edited.
	 */
	public function fields( WP_User $user ): void {
		?>
		<h2><?php esc_html_e( 'Blog author box', 'aktheme' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="aktheme_job_title"><?php esc_html_e( 'Job title', 'aktheme' ); ?></label></th>
				<td>
					<input type="text" name="aktheme_job_title" id="aktheme_job_title" class="regular-text" value="<?php echo esc_attr( (string) get_user_meta( $user->ID, self::JOB_TITLE, true ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Shown under your name in the author box of your articles.', 'aktheme' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="aktheme_author_photo"><?php esc_html_e( 'Author photo', 'aktheme' ); ?></label></th>
				<td>
					<?php $photo_id = $this->photo_id( $user->ID ); ?>
					<input type="url" name="aktheme_author_photo" id="aktheme_author_photo" class="regular-text code" value="<?php echo esc_attr( $photo_id ? (string) wp_get_attachment_url( $photo_id ) : '' ); ?>" <?php disabled( ! user_can( $user, 'upload_files' ) ); ?> />
					<p class="description"><?php esc_html_e( 'Address of a square image from this site\'s Media Library: Media > Library > the image > Copy URL. Images from other sites are not accepted. Used instead of the Gravatar picture. Your LinkedIn link is the Website field above.', 'aktheme' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the fields (nonce and capability checked).
	 *
	 * @since 1.1.21
	 *
	 * @param int $user_id The user being saved.
	 */
	public function save( int $user_id ): void {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'update-user_' . $user_id ) || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( isset( $_POST['aktheme_job_title'] ) ) {
			update_user_meta( $user_id, self::JOB_TITLE, sanitize_text_field( wp_unslash( $_POST['aktheme_job_title'] ) ) );
		}

		if ( isset( $_POST['aktheme_author_photo'] ) && user_can( $user_id, 'upload_files' ) ) {
			$url = esc_url_raw( wp_unslash( $_POST['aktheme_author_photo'] ) );
			$id  = $this->attachment_id( $url );

			// Only an image in this site's Media Library; anything else clears the photo.
			if ( $id && wp_attachment_is_image( $id ) ) {
				update_user_meta( $user_id, self::PHOTO_ID, $id );
			} else {
				delete_user_meta( $user_id, self::PHOTO_ID );
			}

			delete_user_meta( $user_id, self::PHOTO );
		}
	}

	/**
	 * Use the author photo as the avatar.
	 *
	 * @since 1.1.21
	 *
	 * @param array<string, mixed> $args        Avatar data.
	 * @param mixed                $id_or_email User ID, email, WP_User, WP_Post or WP_Comment.
	 *
	 * @return array<string, mixed> Avatar data.
	 */
	public function avatar( array $args, $id_or_email ): array {
		$user_id  = $this->user_id( $id_or_email );
		$photo_id = $user_id ? $this->photo_id( $user_id ) : 0;
		$size     = (int) ( $args['size'] ?? 96 );
		$url      = $photo_id ? wp_get_attachment_image_url( $photo_id, $size > 150 ? 'medium' : 'thumbnail' ) : false;

		if ( is_string( $url ) && '' !== $url ) {
			$args['url'] = $url;
		}

		return $args;
	}

	/**
	 * The author photo of a user who may upload files: the attachment ID, or a photo address saved before 1.3.0 when it
	 * is an image in this site's Media Library.
	 *
	 * @since 1.3.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int Attachment ID, or 0.
	 */
	public function photo_id( int $user_id ): int {
		if ( ! user_can( $user_id, 'upload_files' ) ) {
			return 0;
		}

		$id = (int) get_user_meta( $user_id, self::PHOTO_ID, true );

		if ( ! $id ) {
			$legacy = (string) get_user_meta( $user_id, self::PHOTO, true );
			$id     = $this->attachment_id( $legacy );
		}

		return $id && wp_attachment_is_image( $id ) ? $id : 0;
	}

	/**
	 * The Media Library image at an address on this site, cached (the lookup is a database query).
	 *
	 * @since 1.3.0
	 *
	 * @param string $url Image address.
	 *
	 * @return int Attachment ID, or 0.
	 */
	private function attachment_id( string $url ): int {
		if ( '' === $url ) {
			return 0;
		}

		$key = 'author-photo-' . md5( $url );
		$id  = wp_cache_get( $key, 'aktheme' );

		if ( false === $id ) {
			$id = attachment_url_to_postid( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- Cached here; the VIP helper only exists on VIP.
			wp_cache_set( $key, $id, 'aktheme', HOUR_IN_SECONDS );
		}

		return (int) $id;
	}

	/**
	 * Put the job title under the name in the Post Author block.
	 *
	 * @since 1.1.21
	 *
	 * @param string               $content Block HTML.
	 * @param array<string, mixed> $block   Parsed block.
	 * @param WP_Block|null        $instance Block instance.
	 *
	 * @return string Block HTML.
	 */
	public function job_title( string $content, array $block, $instance = null ): string {
		$post_id = $instance instanceof WP_Block ? (int) ( $instance->context['postId'] ?? 0 ) : (int) get_the_ID();
		$post    = $post_id ? get_post( $post_id ) : null;
		$title   = $post ? (string) get_user_meta( (int) $post->post_author, self::JOB_TITLE, true ) : '';

		if ( '' === $title ) {
			return $content;
		}

		return (string) preg_replace(
			'#(<p class="wp-block-post-author__name">.*?</p>)#s',
			'$1<p class="wp-block-post-author__role">' . esc_html( $title ) . '</p>',
			$content,
			1
		);
	}

	/**
	 * The user behind an avatar request.
	 *
	 * @param mixed $id_or_email User ID, email, WP_User, WP_Post or WP_Comment.
	 *
	 * @return int User ID, or 0.
	 */
	private function user_id( $id_or_email ): int {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}

		if ( $id_or_email instanceof WP_User ) {
			return (int) $id_or_email->ID;
		}

		if ( $id_or_email instanceof WP_Post ) {
			return (int) $id_or_email->post_author;
		}

		if ( $id_or_email instanceof WP_Comment ) {
			return (int) $id_or_email->user_id;
		}

		if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );

			return $user ? (int) $user->ID : 0;
		}

		return 0;
	}
}
