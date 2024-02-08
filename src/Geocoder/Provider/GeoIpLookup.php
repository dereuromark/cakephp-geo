<?php

/**
 * @license MIT License
 */

namespace Geo\Geocoder\Provider;

use Cake\Http\Client;
use Cake\Utility\Xml;
use Geocoder\Collection;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

/**
 * @author Mark Scherer
 */
class GeoIpLookup extends AbstractHttpProvider {

	/**
	 * @var string
	 */
	public const ENDPOINT_URL = 'https://api.geoiplookup.net/?query=%s';

	/**
	 * @var string
	 */
	protected $userAgent;

	/**
	 * @var string
	 */
	protected $referer;

	/**
	 * @param \Cake\Http\Client $client an HTTP client
	 * @param string $userAgent Value of the User-Agent header
	 * @param string $referer Value of the Referer header
	 */
	public function __construct(Client $client, string $userAgent, string $referer = '') {
		parent::__construct($client);

		$this->userAgent = $userAgent;
		$this->referer = $referer;
	}

	/**
	 * @param string $address
	 * @return \Geocoder\Collection
	 */
	public function geocode(string $address) {
		if (!filter_var($address, FILTER_VALIDATE_IP)) {
			throw new UnsupportedOperation('The geoiplookup.net provider does not support street addresses.');
		}

		if (in_array($address, ['127.0.0.1', '::1'])) {
			return $this->returnLocalhostDefaults();
		}

		$query = GeocodeQuery::create($address);

		return $this->geocodeQuery($query);
	}

	/**
	 * Returns the results for the 'localhost' special case.
	 *
	 * @return array
	 */
	protected function getLocalhostDefaults() {
		return [
			'locality' => 'localhost',
			'country' => 'localhost',
		];
	}

	/**
	 * @inheritDoc
	 */
	public function geocodeQuery(GeocodeQuery $query): Collection {
		$address = $query->getText();

		return $this->executeQuery($address);
	}

	/**
	 * @inheritDoc
	 */
	public function reverseQuery(ReverseQuery $query): Collection {
		throw new UnsupportedOperation('The geoiplookup.net provider is not able to do reverse geocoding.');
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'geo_ip_lookup';
	}

	/**
	 * @param string $address
	 *
	 * @return \Geocoder\Model\AddressCollection
	 */
	protected function executeQuery(string $address) {
		$url = sprintf(static::ENDPOINT_URL, $address);
		$request = $this->getRequest($url);
		$request = $request->withHeader('User-Agent', $this->userAgent);

		if ($this->referer) {
			$request = $request->withHeader('Referer', $this->referer);
		}

		$response = $this->getParsedResponse($request);
		if (empty($response)) {
			throw new InvalidServerResponse(sprintf('Could not execute query %s', $address));
		}

		/** @var \DOMDocument|\SimpleXMLElement|null $data */
		$data = Xml::build($response);
		if ($data === null || empty($data->results)) {
			return new AddressCollection([]);
		}

		$data = (array)$data->results;
		/*
		 * ip, host, isp, city, countrycode, countryname, latitude, longitude
		 */
		$data = (array)$data['result'];

		$adminLevels = [];
		if (!empty($data['isp'])) {
			$adminLevels[] = new AdminLevel(1, $data['isp']);
		}

		$builder = new AddressBuilder($this->getName());
		$builder->setCoordinates($data['latitude'], $data['longitude']);
		$builder->setCountryCode($data['countrycode']);
		$builder->setCountry($data['countryname']);
		$builder->setAdminLevels($adminLevels);
		$builder->setLocality($data['city']);

		/** @var \Geo\Geocoder\Provider\GeoIpAddress $ipAddress */
		$ipAddress = $builder->build(GeoIpAddress::class);
		$ipAddress = $ipAddress->withHost($data['host']);

		return new AddressCollection([$ipAddress]);
	}

	/**
	 * @param string $default
	 *
	 * @return \Geocoder\Model\AddressCollection
	 */
	protected function returnLocalhostDefaults(string $default = 'localhost') {
		$builder = new AddressBuilder($this->getName());
		$builder->setLocality($default);

		/** @var \Geo\Geocoder\Provider\GeoIpAddress $ipAddress */
		$ipAddress = $builder->build(GeoIpAddress::class);
		$ipAddress = $ipAddress->withHost($default);

		return new AddressCollection([$ipAddress]);
	}

}
