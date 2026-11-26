<?php
/**
 * Registers the theme's abilities with the WordPress Abilities API.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Abilities;

use rtCamp\WPPrimitives\Contracts\Abstracts\AbstractAbilityRegistrar;

/**
 * Class Registrar
 *
 * The site's design and content as abilities an AI agent (or any Abilities API client) can discover and call: read and
 * change the design tokens and global styles, the header, footer and other template parts, the menus, the shared
 * sections and the pages, and undo a design change. Every change is stored where the Site Editor stores it (database
 * entities with revisions), never in the theme's files, so a theme update keeps it and an editor can see and undo it.
 *
 * Needs WordPress 6.9 or later (the Abilities API); on older versions nothing registers. To reach the abilities from an
 * MCP client, install the MCP Adapter plugin: they appear on its default server.
 *
 * @since 1.3.0
 */
class Registrar extends AbstractAbilityRegistrar {

	/**
	 * Ability classes, readers first.
	 *
	 * @var list<class-string<Ability>>
	 */
	public const ABILITIES = [
		Design\GetDesign::class,
		Design\UpdateDesign::class,
		Design\RestoreDesign::class,
		Templates\ListTemplateParts::class,
		Templates\GetTemplatePart::class,
		Templates\UpdateTemplatePart::class,
		Templates\ResetTemplatePart::class,
		Menus\ListMenus::class,
		Menus\UpdateMenu::class,
		Content\ListBuildingBlocks::class,
		Content\UpdateSection::class,
		Content\GetPages::class,
		Content\CreatePage::class,
		Content\UpdatePage::class,
		Settings\GetSettings::class,
		Settings\UpdateSettings::class,
	];

	/**
	 * {@inheritDoc}
	 */
	protected function category_slug(): string {
		return Ability::CATEGORY;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function category_label(): string {
		return __( 'AK Theme: site design and content', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function category_description(): string {
		return __( 'Read and change this site\'s design (colours, fonts, sizes, global styles), its header, footer and other template parts, its menus, its shared sections and its pages. Changes are saved like Site Editor changes, with revisions; start with aktheme/get-site-design and aktheme/list-building-blocks.', 'aktheme' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return Ability[] Abilities.
	 */
	protected function abilities(): array {
		/**
		 * Filters the theme's ability classes, e.g. to drop the ones that change content on a site where only people
		 * should do that, or to add a client's own.
		 *
		 * @since 1.3.0
		 *
		 * @param list<class-string<Ability>> $classes Ability classes.
		 */
		$classes = (array) apply_filters( 'aktheme_abilities', self::ABILITIES );

		return array_values(
			array_filter(
				array_map( static fn ( $class_name ) => is_string( $class_name ) && is_subclass_of( $class_name, Ability::class ) ? new $class_name() : null, $classes )
			)
		);
	}
}
