<?php
/**
 * Ability: read the theme settings.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Settings;

use AK\Theme\Core\Settings;
use AK\Theme\Modules\Abilities\Ability;

/**
 * Class GetSettings
 *
 * `aktheme/get-theme-settings`: the per-site switches (see Core\Settings).
 *
 * @since 1.3.0
 */
class GetSettings extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'get-theme-settings';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function is_readonly(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function capability(): string {
		return 'manage_options';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'Get theme settings', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Returns the theme settings: blog_article_inserts (the program card, skills banner and recommended reading inside blog posts), company_linkedin_url (author box fallback link) and youtube_consent (ask before YouTube players load).', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return [];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function output_schema(): array {
		return Settings::schema();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $input Unused.
	 */
	public function execute( mixed $input ): mixed {
		unset( $input );

		return Settings::all();
	}
}
