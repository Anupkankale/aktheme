<?php
/**
 * Theme bootstrap file.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme;

use rtCamp\WPPrimitives\Contracts\Traits\{Singleton, Loader};
use AK\Theme\Core\{Assets, Components, FeatureRegistry, Logger, Menu, Settings, Templates, ThemeSetup};

use AK\Theme\Modules\Blocks\{Blocks, PageStyles};

use AK\Theme\Modules\Media\{Images, Video};

/**
 * Class Main
 *
 * @since 1.0.0
 */
class Main {

	use Singleton;
	use Loader;

	/**
	 * List of classes to load.
	 */
	public const CLASSES = [
		Assets::class,
		Menu::class,
		ThemeSetup::class,
		Components::class,
		Templates::class,
		FeatureRegistry::class,
		Logger::class,
		Settings::class,
		Blocks::class,
		PageStyles::class,
		Images::class,
		Video::class,
		Modules\Shortcodes\CurrentYear::class,
		Modules\Shortcodes\ShortcodeBlock::class,
		Modules\Patterns\SyncedSections::class,
		Modules\Typography\LineBreaks::class,
		Modules\Menus\MenuSeeder::class,
		Modules\Menus\MenuRenderer::class,
		Modules\Blog\SinglePost::class,
		Modules\Blog\AuthorProfile::class,
		Modules\Abilities\Registrar::class,
		Modules\AI\McpServer::class,
		Modules\AI\Assistant::class,
		Modules\AI\SettingsPage::class,
	];

	/**
	 * Constructor.
	 */
	protected function __construct() {
		static::$instance = $this;
		$this->load( self::CLASSES );
	}
}
