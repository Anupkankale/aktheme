<?php
/**
 * Shared sections as synced patterns: fix once, shown everywhere.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Patterns;

use rtCamp\WPPrimitives\Contracts\Interfaces\Registrable;
use WP_Block_Patterns_Registry;

/**
 * Class SyncedSections
 *
 * The sections that several pages share (offer banner, client testimonials, the closing call to action, achievements,
 * the career table, and every photo with labels on it) live in WordPress as synced patterns (Patterns > My patterns).
 * Every page shows the same one, so an edit made on any page, or in the pattern itself, changes all of them. The photo
 * sections ("photo-*") let each page override the photo and the label text (pattern overrides); their layout and
 * design stay shared.
 *
 * The first time an administrator opens wp-admin, this creates each one from the theme's hidden source pattern. It
 * never updates or duplicates them afterwards (the team owns them from then on), and only recreates one that was
 * deleted for good. The page patterns use block() to place them. They are filed under "AK: Shared Sections
 * (reusable)" in the inserter and in Patterns.
 *
 * @since 1.1.5
 */
class SyncedSections implements Registrable {

	/**
	 * Option holding the synced pattern ID of each section.
	 *
	 * @since 1.1.5
	 * @var string
	 */
	public const OPTION = 'aktheme_synced_sections';

	/**
	 * Option holding the version of the setup that last ran (bumped when existing sections need a one-time update).
	 *
	 * @since 1.1.6
	 * @var string
	 */
	public const VERSION_OPTION = 'aktheme_synced_sections_version';

	/**
	 * Setup version: bump it when existing sections need a one-time update.
	 *
	 * @since 1.1.6
	 * @var string
	 */
	public const VERSION = '1';

	/**
	 * Pattern category (wp_pattern_category term) the shared sections are filed under in the inserter and Patterns.
	 *
	 * @since 1.1.6
	 * @var string
	 */
	public const CATEGORY = 'ak-shared';

	/**
	 * Post meta that marks a synced pattern as a theme section (its value is the section key).
	 *
	 * @since 1.1.5
	 * @var string
	 */
	public const META = '_aktheme_section';

	/**
	 * The shared sections: key => title of the synced pattern and the theme pattern it starts from. Optional:
	 * `copy` starts it as a copy of another section's synced pattern as the team has edited it (the theme pattern only
	 * when that one doesn't exist), `replace` swaps text in that copy, and `was` is an older title that is renamed to
	 * `title` (a title the team chose is kept).
	 *
	 * @since 1.1.5
	 * @var array<string, array{title: string, pattern: string, copy?: string, replace?: array<string, string>, was?: string}>
	 */
	public const SECTIONS = [
		'offer-banner'         => [
			'title'   => 'Offer Banner',
			'pattern' => 'aktheme/offer-banner',
		],
		'clients-testimonials' => [
			'title'   => 'Testimonials – Global',
			'pattern' => 'aktheme/clients-testimonials',
		],
		'cta-next-chapter'     => [
			'title'   => 'CTA: Next Step',
			'pattern' => 'aktheme/cta-next-chapter',
		],
		'achievements'         => [
			'title'   => 'Our Achievements',
			'pattern' => 'aktheme/achievements',
		],
		'photo-why-1'          => [
			'title'   => 'Photo: Feature 1',
			'pattern' => 'aktheme/photo-why-1',
		],
	];

	/**
	 * Register hooks.
	 *
	 * @since 1.1.5
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', [ $this, 'maybe_seed' ] );
		add_action( 'after_switch_theme', [ $this, 'maybe_seed' ] );
	}

	/**
	 * Create the missing sections, for administrators (their content is saved unfiltered, like the editor would).
	 *
	 * @since 1.1.5
	 */
	public function maybe_seed(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$ids     = (array) get_option( self::OPTION, [] );
		$missing = self::VERSION !== get_option( self::VERSION_OPTION );

		foreach ( array_keys( self::SECTIONS ) as $key ) {
			$missing = $missing || empty( $ids[ $key ] ) || ! get_post( (int) $ids[ $key ] );
		}

		if ( $missing ) {
			$this->seed();
			update_option( self::VERSION_OPTION, self::VERSION, false );
		}
	}

	/**
	 * Create every section that has no synced pattern yet.
	 *
	 * @since 1.1.5
	 *
	 * @return int Number of synced patterns created.
	 */
	public function seed(): int {
		$ids      = (array) get_option( self::OPTION, [] );
		$created  = 0;
		$category = $this->category();

		foreach ( self::SECTIONS as $key => $section ) {
			$existing = $this->find( $key );

			if ( $existing ) {
				$ids[ $key ] = $existing;
				$this->file( $existing, $category );
				$this->rename( $existing, $section );
				continue;
			}

			$content = $this->source( $section, $ids );

			if ( '' === $content ) {
				continue;
			}

			$id = wp_insert_post(
				wp_slash(
					[
						'post_type'    => 'wp_block',
						'post_status'  => 'publish',
						'post_title'   => $section['title'],
						'post_content' => $content,
						'meta_input'   => [ self::META => $key ],
					]
				),
				true
			);

			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}

			$ids[ $key ] = (int) $id;
			$this->file( (int) $id, $category );
			++$created;
		}

		update_option( self::OPTION, $ids, true );

		return $created;
	}

	/**
	 * The synced pattern ID of a section.
	 *
	 * @since 1.1.5
	 *
	 * @param string $key Section key.
	 *
	 * @return int Synced pattern ID, or 0 when it does not exist (yet).
	 */
	public static function id( string $key ): int {
		$ids = (array) get_option( self::OPTION, [] );
		$id  = (int) ( $ids[ $key ] ?? 0 );

		return $id && 'wp_block' === get_post_type( $id ) ? $id : 0;
	}

	/**
	 * Block markup that places a section in a page pattern: the synced pattern, or the theme's source pattern until
	 * the synced one exists (before an administrator has opened wp-admin).
	 *
	 * @since 1.1.5
	 *
	 * @param string $key Section key.
	 *
	 * @return string Block markup, or an empty string for an unknown section.
	 */
	public static function block( string $key ): string {
		if ( ! isset( self::SECTIONS[ $key ] ) ) {
			return '';
		}

		$id = self::id( $key );

		if ( $id ) {
			return '<!-- wp:block ' . wp_json_encode( [ 'ref' => $id ] ) . ' /-->';
		}

		return '<!-- wp:pattern ' . wp_json_encode( [ 'slug' => self::SECTIONS[ $key ]['pattern'] ], JSON_UNESCAPED_SLASHES ) . ' /-->';
	}

	/**
	 * The shared sections' pattern category, created when missing.
	 *
	 * @since 1.1.6
	 *
	 * @return int Term ID, or 0 when it could not be created.
	 */
	/**
	 * What a new section starts with: a copy of another section's synced pattern (as edited), else the theme pattern.
	 *
	 * @param array<string, mixed> $section The section.
	 * @param array<string, mixed> $ids     Synced pattern IDs found so far, by key.
	 *
	 * @return string Block markup, or '' when there is none.
	 */
	private function source( array $section, array $ids ): string {
		$copy = isset( $section['copy'] ) ? get_post( (int) ( $ids[ (string) $section['copy'] ] ?? 0 ) ) : null;

		if ( $copy && 'wp_block' === $copy->post_type && '' !== $copy->post_content ) {
			$replace = (array) ( $section['replace'] ?? [] );

			return strtr( (string) $copy->post_content, array_map( 'strval', $replace ) );
		}

		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( (string) $section['pattern'] );

		return $pattern && ! empty( $pattern['content'] ) ? (string) $pattern['content'] : '';
	}

	/**
	 * Give a section its current title when it still has the older one (a title the team chose is kept).
	 *
	 * @param int                  $id      Synced pattern ID.
	 * @param array<string, mixed> $section The section.
	 */
	private function rename( int $id, array $section ): void {
		if ( empty( $section['was'] ) || get_the_title( $id ) !== $section['was'] ) {
			return;
		}

		wp_update_post(
			wp_slash(
				[
					'ID'         => $id,
					'post_title' => (string) $section['title'],
				]
			)
		);
	}

	private function category(): int {
		$term = get_term_by( 'slug', self::CATEGORY, 'wp_pattern_category' );

		if ( $term ) {
			return (int) $term->term_id;
		}

		$made = wp_insert_term( __( 'AK: Shared Sections (reusable)', 'aktheme' ), 'wp_pattern_category', [ 'slug' => self::CATEGORY ] );

		return is_wp_error( $made ) ? 0 : (int) $made['term_id'];
	}

	/**
	 * Add a synced pattern to the shared category (keeping any category the team gave it).
	 *
	 * @since 1.1.6
	 *
	 * @param int $id      Synced pattern ID.
	 * @param int $term_id Category term ID.
	 */
	private function file( int $id, int $term_id ): void {
		if ( $term_id ) {
			wp_set_object_terms( $id, [ $term_id ], 'wp_pattern_category', true );
		}
	}

	/**
	 * Find the synced pattern already made for a section (also when the option was lost).
	 *
	 * @since 1.1.5
	 *
	 * @param string $key Section key.
	 *
	 * @return int Synced pattern ID, or 0.
	 */
	private function find( string $key ): int {
		$id = self::id( $key );

		if ( $id ) {
			return $id;
		}

		$found = get_posts(
			[
				'post_type'   => 'wp_block',
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- runs once, in wp-admin.
				'meta_value'  => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- runs once, in wp-admin.
			]
		);

		return $found ? (int) $found[0] : 0;
	}
}
