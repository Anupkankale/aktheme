<?php
/**
 * Default menu items: placeholders the header and footer start with.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

namespace AK\Theme\Modules\Menus;

/**
 * Class DefaultMenus
 *
 * The links each menu location starts with. MenuSeeder creates these menus once; after that they are managed in
 * Appearance > Menus.
 *
 * @since 1.2.0
 */
class DefaultMenus {

	/**
	 * Menus by location.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, array{name: string, items: array<int, array<string, mixed>>}> Location => menu.
	 */
	public static function all(): array {
		return [
			'primary' => [
				'name'  => 'Primary Menu',
				'items' => [
					[
						'title'    => 'Services',
						'url'      => '/services/',
						'children' => [
							[
								'title' => 'Service One',
								'url'   => '/services/service-one/',
							],
							[
								'title' => 'Service Two',
								'url'   => '/services/service-two/',
							],
							[
								'title' => 'Service Three',
								'url'   => '/services/service-three/',
							],
						],
					],
					[
						'title'    => 'Company',
						'url'      => '#',
						'children' => [
							[
								'title' => 'About Us',
								'url'   => '/about-us/',
							],
							[
								'title' => 'Case Studies',
								'url'   => '/case-studies/',
							],
							[
								'title' => 'Testimonials',
								'url'   => '/testimonials/',
							],
						],
					],
					[
						'title' => 'Blog',
						'url'   => '/blog/',
					],
					[
						'title'   => 'Contact Us',
						'url'     => '/contact/',
						'classes' => 'ak-nav-contact',
					],
				],
			],
			'footer-main' => [
				'name'  => 'Footer: Column 1',
				'items' => [
					[
						'title' => 'About Us',
						'url'   => '/about-us/',
					],
					[
						'title' => 'Blog',
						'url'   => '/blog/',
					],
					[
						'title' => 'Privacy Policy',
						'url'   => '/privacy-policy/',
					],
					[
						'title' => 'Sitemap',
						'url'   => '/sitemap_index.xml',
					],
					[
						'title' => 'Contact Us',
						'url'   => '/contact/',
					],
				],
			],
			'footer-col-2' => [
				'name'  => 'Footer: Column 2',
				'items' => [
					[
						'title' => 'Link One',
						'url'   => '/',
					],
					[
						'title' => 'Link Two',
						'url'   => '/',
					],
					[
						'title' => 'Link Three',
						'url'   => '/',
					],
				],
			],
			'footer-col-3' => [
				'name'  => 'Footer: Column 3',
				'items' => [
					[
						'title' => 'Link One',
						'url'   => '/',
					],
					[
						'title' => 'Link Two',
						'url'   => '/',
					],
					[
						'title' => 'Link Three',
						'url'   => '/',
					],
				],
			],
			'footer-col-4' => [
				'name'  => 'Footer: Column 4',
				'items' => [
					[
						'title' => 'Service One',
						'url'   => '/services/service-one/',
					],
					[
						'title' => 'Service Two',
						'url'   => '/services/service-two/',
					],
					[
						'title' => 'Service Three',
						'url'   => '/services/service-three/',
					],
				],
			],
			'footer-region-1' => [
				'name'  => 'Footer: Region 1',
				'items' => [
					[
						'title' => 'Location One',
						'url'   => '/',
					],
					[
						'title' => 'Location Two',
						'url'   => '/',
					],
				],
			],
			'footer-region-2' => [
				'name'  => 'Footer: Region 2',
				'items' => [
					[
						'title' => 'Location One',
						'url'   => '/',
					],
					[
						'title' => 'Location Two',
						'url'   => '/',
					],
				],
			],
			'footer-region-3' => [
				'name'  => 'Footer: Region 3',
				'items' => [
					[
						'title' => 'Location One',
						'url'   => '/',
					],
					[
						'title' => 'Location Two',
						'url'   => '/',
					],
				],
			],
			'blog' => [
				'name'  => 'Blog Categories',
				'items' => [
					[
						'title' => 'News',
						'url'   => '/blog/',
					],
					[
						'title' => 'Guides',
						'url'   => '/blog/',
					],
					[
						'title' => 'Updates',
						'url'   => '/blog/',
					],
				],
			],
		];
	}
}
