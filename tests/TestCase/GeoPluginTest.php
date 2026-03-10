<?php
declare(strict_types=1);

namespace Geo\Test\TestCase;

use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Geo\GeoPlugin;

/**
 * @uses \Geo\GeoPlugin
 */
class GeoPluginTest extends TestCase {

	/**
	 * @var \Geo\GeoPlugin
	 */
	protected GeoPlugin $plugin;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->plugin = new GeoPlugin();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		Router::reload();

		parent::tearDown();
	}

	/**
	 * Test that middleware is disabled by default.
	 *
	 * @return void
	 */
	public function testMiddlewareDisabled(): void {
		$this->assertFalse($this->plugin->isEnabled('middleware'));
	}

	/**
	 * Test that console is disabled by default.
	 *
	 * @return void
	 */
	public function testConsoleDisabled(): void {
		$this->assertFalse($this->plugin->isEnabled('console'));
	}

	/**
	 * Test that routes are enabled by default.
	 *
	 * @return void
	 */
	public function testRoutesEnabled(): void {
		$this->assertTrue($this->plugin->isEnabled('routes'));
	}

	/**
	 * Test routes method registers admin routes.
	 *
	 * @return void
	 */
	public function testRoutes(): void {
		$builder = Router::createRouteBuilder('/');
		$this->plugin->routes($builder);

		$routes = Router::routes();
		$this->assertNotEmpty($routes);

		// Check that the admin/geo index route is registered
		$url = Router::url([
			'prefix' => 'Admin',
			'plugin' => 'Geo',
			'controller' => 'Geo',
			'action' => 'index',
		]);
		$this->assertSame('/admin/geo', $url);
	}

	/**
	 * Test routes method registers fallback routes.
	 *
	 * @return void
	 */
	public function testRoutesWithFallbacks(): void {
		$builder = Router::createRouteBuilder('/');
		$this->plugin->routes($builder);

		// Test that fallbacks work for other controller/actions
		$url = Router::url([
			'prefix' => 'Admin',
			'plugin' => 'Geo',
			'controller' => 'GeocodedAddresses',
			'action' => 'index',
		]);
		// Fallback uses default route class (not DashedRoute)
		$this->assertStringContainsString('/admin/geo/', $url);
		$this->assertStringContainsString('GeocodedAddresses', $url);
	}

}
