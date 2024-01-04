<?php

namespace Geo\Geocoder;

use Cake\Http\Client\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface {

	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @param array<string, mixed> $headers
	 * @param string|null $body
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = '', array $headers = [], ?string $body = null): ResponseInterface {
		return new Response($headers, (string)$body);
	}

}
