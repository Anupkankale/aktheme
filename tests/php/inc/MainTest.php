<?php
/**
 * Test Main class.
 *
 * @package AK\Theme
 */

declare( strict_types = 1 );

use AK\Theme\Core\Components;
use AK\Theme\Main;
use AK\Theme\Tests\TestCase;

/**
 * Class MainTest
 *
 * @since 1.0.0
 */
class MainTest extends TestCase {

	/**
	 * Test if class Main exists.
	 *
	 * @since 1.0.0
	 */
	public function test_class_exists(): void {
		$this->assertTrue( class_exists( 'AK\Theme\Main' ) );
	}

	/**
	 * Test if class Main is a singleton.
	 *
	 * @since 1.0.0
	 */
	public function test_class_is_singleton(): void {
		$this->assertInstanceOf( Main::class, Main::get_instance() );
	}

	/**
	 * Test all classes registered in CLASSES constant exist.
	 *
	 * @since 1.0.0
	 */
	public function test_registered_classes_exist(): void {
		foreach ( Main::CLASSES as $class ) {
			$this->assertTrue( class_exists( $class ), "Class {$class} does not exist." );
		}
	}

	/**
	 * Test component loader is shared by the bootstrap container.
	 *
	 * @since 1.0.0
	 */
	public function test_component_loader_is_shared(): void {
		$this->assertInstanceOf(
			Components::class,
			Main::get_instance()->get_shared( Components::class )
		);
	}
}
