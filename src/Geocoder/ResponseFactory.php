<?php

namespace Geo\Geocoder;

use Cake\Http\Client\Response;
use Http\Message\ResponseFactory as ResponseFactoryInterface;

class ResponseFactory implements ResponseFactoryInterface {

	/**
	 * @inheritDoc
	 */
	public function createResponse($statusCode = 200, $reasonPhrase = null, array $headers = [], $body = null, $protocolVersion = '1.1') {
		return new Response($headers, (string)$body);
	}

}
