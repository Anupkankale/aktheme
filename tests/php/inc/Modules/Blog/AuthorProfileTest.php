<?php
/**
 * Test AuthorProfile class.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Modules\Blog\AuthorProfile;
use AK\Theme\Tests\TestCase;

/**
 * Class AuthorProfileTest
 *
 * @since 1.1.21
 */
class AuthorProfileTest extends TestCase {

	/**
	 * Instance under test.
	 *
	 * @var AuthorProfile
	 */
	private AuthorProfile $instance;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->instance = new AuthorProfile();
	}

	/**
	 * Test class implements Registrable.
	 */
	public function test_implements_registrable(): void {
		$this->assertInstanceOf( 'rtCamp\WPPrimitives\Contracts\Interfaces\Registrable', $this->instance );
	}

	/**
	 * Test the hooks are registered.
	 */
	public function test_registers_hooks(): void {
		$this->instance->register_hooks();

		$this->assertGreaterThan( 0, has_action( 'show_user_profile', [ $this->instance, 'fields' ] ) );
		$this->assertGreaterThan( 0, has_action( 'edit_user_profile', [ $this->instance, 'fields' ] ) );
		$this->assertGreaterThan( 0, has_action( 'personal_options_update', [ $this->instance, 'save' ] ) );
		$this->assertGreaterThan( 0, has_action( 'edit_user_profile_update', [ $this->instance, 'save' ] ) );
		$this->assertGreaterThan( 0, has_filter( 'pre_get_avatar_data', [ $this->instance, 'avatar' ] ) );
		$this->assertGreaterThan( 0, has_filter( 'render_block_core/post-author', [ $this->instance, 'job_title' ] ) );
	}

	/**
	 * Test saving the fields needs a valid nonce and the right to edit that user, and cleans the values.
	 */
	public function test_save(): void {
		$editor = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$author = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $editor );

		$image = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );

		$_POST = [
			'_wpnonce'             => wp_create_nonce( 'update-user_' . $author ),
			'aktheme_job_title'    => ' Content Strategist <b>|</b> cSuite ',
			'aktheme_author_photo' => wp_get_attachment_url( $image ),
		];
		$this->instance->save( $author );

		$this->assertSame( 'Content Strategist | cSuite', get_user_meta( $author, AuthorProfile::JOB_TITLE, true ) );
		$this->assertSame( $image, (int) get_user_meta( $author, AuthorProfile::PHOTO_ID, true ) );

		// An image from another site is not stored: it clears the photo.
		$_POST = [
			'_wpnonce'             => wp_create_nonce( 'update-user_' . $author ),
			'aktheme_author_photo' => 'https://tracker.example.net/pixel.gif',
		];
		$this->instance->save( $author );

		$this->assertSame( '', get_user_meta( $author, AuthorProfile::PHOTO_ID, true ) );

		// Without a valid nonce nothing changes.
		$_POST = [
			'_wpnonce'          => 'nope',
			'aktheme_job_title' => 'Changed',
		];
		$this->instance->save( $author );

		$this->assertSame( 'Content Strategist | cSuite', get_user_meta( $author, AuthorProfile::JOB_TITLE, true ) );

		// An author can't change another user.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'author' ] ) );
		$_POST = [
			'_wpnonce'          => wp_create_nonce( 'update-user_' . $author ),
			'aktheme_job_title' => 'Changed',
		];
		$this->instance->save( $author );

		$this->assertSame( 'Content Strategist | cSuite', get_user_meta( $author, AuthorProfile::JOB_TITLE, true ) );
		$_POST = [];
	}

	/**
	 * Test the author photo replaces the avatar, for a user ID, a user object, a post and an email.
	 */
	public function test_avatar_uses_the_author_photo(): void {
		$author = self::factory()->user->create(
			[
				'user_email' => 'writer@example.com',
				'role'       => 'author',
			]
		);
		$image  = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		update_user_meta( $author, AuthorProfile::PHOTO_ID, $image );
		$post = self::factory()->post->create_and_get( [ 'post_author' => $author ] );

		foreach ( [ $author, get_user_by( 'id', $author ), $post, 'writer@example.com' ] as $who ) {
			$this->assertSame( wp_get_attachment_image_url( $image, 'thumbnail' ), $this->instance->avatar( [], $who )['url'] );
		}

		$this->assertArrayNotHasKey( 'url', $this->instance->avatar( [], self::factory()->user->create() ) );
	}

	/**
	 * Test a photo is ignored for a user who can't upload files, and an old photo address only counts when it is an
	 * image in the Media Library.
	 *
	 * @since 1.3.0
	 */
	public function test_avatar_ignores_photos_from_elsewhere(): void {
		$image      = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		update_user_meta( $subscriber, AuthorProfile::PHOTO_ID, $image );

		$this->assertArrayNotHasKey( 'url', $this->instance->avatar( [], $subscriber ) );

		$author = self::factory()->user->create( [ 'role' => 'author' ] );
		update_user_meta( $author, AuthorProfile::PHOTO, 'https://tracker.example.net/pixel.gif' );

		$this->assertArrayNotHasKey( 'url', $this->instance->avatar( [], $author ) );

		update_user_meta( $author, AuthorProfile::PHOTO, wp_get_attachment_url( $image ) );

		$this->assertSame( $image, $this->instance->photo_id( $author ) );
	}

	/**
	 * Test the job title goes under the name in the Post Author block.
	 */
	public function test_job_title_under_the_name(): void {
		$author = self::factory()->user->create();
		update_user_meta( $author, AuthorProfile::JOB_TITLE, 'Content Strategist' );
		$post_id = self::factory()->post->create( [ 'post_author' => $author ] );
		$html    = '<div class="wp-block-post-author"><div class="wp-block-post-author__content"><p class="wp-block-post-author__name">Writer</p><p class="wp-block-post-author__bio">Bio</p></div></div>';
		$block   = new WP_Block( [ 'blockName' => 'core/post-author' ], [ 'postId' => $post_id ] );

		$this->assertStringContainsString(
			'<p class="wp-block-post-author__name">Writer</p><p class="wp-block-post-author__role">Content Strategist</p>',
			$this->instance->job_title( $html, [], $block )
		);

		$other = new WP_Block( [ 'blockName' => 'core/post-author' ], [ 'postId' => self::factory()->post->create() ] );

		$this->assertSame( $html, $this->instance->job_title( $html, [], $other ) );
	}
}
