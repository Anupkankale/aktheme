<?php
/**
 * Ability: change the theme settings.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities\Settings;

use AK\Theme\Core\Settings;
use AK\Theme\Modules\Abilities\Ability;

/**
 * Class UpdateSettings
 *
 * `aktheme/update-theme-settings`: change some of the per-site switches; the others keep their values.
 *
 * @since 1.3.0
 */
class UpdateSettings extends Ability {

	/**
	 * {@inheritDoc}
	 */
	protected function slug(): string {
		return 'update-theme-settings';
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
	protected function idempotent(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function label(): string {
		return __( 'Update theme settings', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function description(): string {
		return __( 'Changes the given theme settings (see aktheme/get-theme-settings); settings not given keep their values. Returns all settings.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_schema(): array {
		return Settings::schema();
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
	 * @param mixed $input Input.
	 */
	public function execute( mixed $input ): mixed {
		update_option( Settings::OPTION, array_merge( Settings::all(), is_array( $input ) ? $input : [] ) );

		return Settings::all();
	}
}
