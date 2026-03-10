<?php
declare(strict_types=1);

namespace Geo\Test\TestCase\Geocoder;

use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;
use Geo\Geocoder\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * @uses \Geo\Geocoder\ResponseFactory
 */
class ResponseFactoryTest extends TestCase {

	/**
	 * @var \Geo\Geocoder\ResponseFactory
	 */
	protected ResponseFactory $factory;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->factory = new ResponseFactory();
	}

	/**
	 * Test createResponse returns a ResponseInterface.
	 *
	 * @return void
	 */
	public function testCreateResponseReturnsResponseInterface(): void {
		$response = $this->factory->createResponse();

		$this->assertInstanceOf(ResponseInterface::class, $response);
		$this->assertInstanceOf(Response::class, $response);
	}

	/**
	 * Test createResponse with default values.
	 *
	 * @return void
	 */
	public function testCreateResponseWithDefaults(): void {
		$response = $this->factory->createResponse();

		$this->assertInstanceOf(Response::class, $response);
	}

	/**
	 * Test createResponse with custom code.
	 *
	 * @return void
	 */
	public function testCreateResponseWithCode(): void {
		$response = $this->factory->createResponse(404);

		$this->assertInstanceOf(Response::class, $response);
	}

	/**
	 * Test createResponse with reason phrase.
	 *
	 * @return void
	 */
	public function testCreateResponseWithReasonPhrase(): void {
		$response = $this->factory->createResponse(200, 'OK');

		$this->assertInstanceOf(Response::class, $response);
	}

	/**
	 * Test createResponse with headers.
	 *
	 * @return void
	 */
	public function testCreateResponseWithHeaders(): void {
		$headers = ['Content-Type' => 'application/json'];
		$response = $this->factory->createResponse(200, '', $headers);

		$this->assertInstanceOf(Response::class, $response);
	}

	/**
	 * Test createResponse with body.
	 *
	 * @return void
	 */
	public function testCreateResponseWithBody(): void {
		$body = '{"status": "ok"}';
		$response = $this->factory->createResponse(200, '', [], $body);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame($body, (string)$response->getBody());
	}

	/**
	 * Test createResponse with null body.
	 *
	 * @return void
	 */
	public function testCreateResponseWithNullBody(): void {
		$response = $this->factory->createResponse(200, '', [], null);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame('', (string)$response->getBody());
	}

	/**
	 * Test createResponse with all parameters.
	 *
	 * @return void
	 */
	public function testCreateResponseWithAllParameters(): void {
		$headers = ['Content-Type' => 'application/json'];
		$body = '{"message": "test"}';
		$response = $this->factory->createResponse(201, 'Created', $headers, $body);

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame($body, (string)$response->getBody());
	}

}
