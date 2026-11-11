<?php

namespace Geo\Test\TestCase\Controller\Admin;

use Cake\Event\EventInterface;
use Cake\Event\EventManager;
use Cake\Http\MiddlewareQueue;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @uses \Geo\Controller\Admin\GeocodedAddressesController
 */
class GeocodedAddressesControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	protected array $fixtures = [
		'plugin.Geo.GeocodedAddresses',
	];

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->loadPlugins(['Geo']);
	}

	/**
	 * Test index method
	 *
	 * @return void
	 */
	public function testIndex(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * CakePHP's CspMiddleware stores the nonce as `cspScriptNonce`.
	 *
	 * @return void
	 */
	public function testIndexUsesCspScriptNonce(): void {
		$this->disableErrorHandlerMiddleware();
		$this->withRequestAttribute('cspScriptNonce', 'core-nonce');

		$this->get(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('<script nonce="core-nonce">');
		$this->assertResponseNotRegExp('/<script(?![^>]*nonce=)/');
	}

	/**
	 * A custom middleware may still provide the nonce as `cspNonce`.
	 *
	 * @return void
	 */
	public function testViewFallsBackToCspNonce(): void {
		$this->disableErrorHandlerMiddleware();
		$this->withRequestAttribute('cspNonce', 'custom-nonce');

		$this->get(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
		$this->assertResponseContains('<script nonce="custom-nonce">');
	}

	/**
	 * Test view method
	 *
	 * @return void
	 */
	public function testView(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'view', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * Test clearEmpty method
	 *
	 * @return void
	 */
	public function testClearEmpty(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		$this->post(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'clearEmpty']);

		$this->assertResponseCode(302);
		$this->assertRedirect(['action' => 'index']);
		$this->assertFlashMessage('The empty geocoded addresses have been removed from cache.');
	}

	/**
	 * Test clearAll method
	 *
	 * @return void
	 */
	public function testClearAll(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		$this->post(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'clearAll']);

		$this->assertResponseCode(302);
		$this->assertRedirect(['action' => 'index']);
		$this->assertFlashMessage('All geocoded addresses have been removed from cache');
	}

	/**
	 * Test edit method (GET)
	 *
	 * @return void
	 */
	public function testEditGet(): void {
		$this->disableErrorHandlerMiddleware();

		$this->get(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'edit', 1]);

		$this->assertResponseCode(200);
	}

	/**
	 * Test edit method (POST)
	 *
	 * @return void
	 */
	public function testEditPost(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		$this->post(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'edit', 1], [
			'lat' => 2.5,
			'lng' => 2.5,
		]);

		$this->assertResponseCode(302);
		$this->assertRedirect(['action' => 'index']);
		$this->assertFlashMessage('The geocoded address has been saved.');
	}

	/**
	 * Test delete method
	 *
	 * @return void
	 */
	public function testDelete(): void {
		$this->disableErrorHandlerMiddleware();
		$this->enableRetainFlashMessages();

		$this->post(['prefix' => 'Admin', 'plugin' => 'Geo', 'controller' => 'GeocodedAddresses', 'action' => 'delete', 1]);

		$this->assertResponseCode(302);
		$this->assertRedirect(['action' => 'index']);
		$this->assertFlashMessage('The geocoded address has been deleted.');
	}

	/**
	 * Adds a middleware that sets a request attribute, like a CSP middleware would.
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return void
	 */
	protected function withRequestAttribute(string $name, string $value): void {
		EventManager::instance()->on('Server.buildMiddleware', function (EventInterface $event, MiddlewareQueue $queue) use ($name, $value): void {
			$queue->prepend(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($name, $value): ResponseInterface {
				return $handler->handle($request->withAttribute($name, $value));
			});
		});
	}

}
