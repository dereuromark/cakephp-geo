<?php

declare(strict_types=1);

namespace Geo\Geocoder;

use Geocoder\Location;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Bounds;
use Geocoder\Model\Coordinates;
use Geocoder\Model\Country;

/**
 * A Location implementation for cached geocoding results.
 *
 * This class can be reconstructed from JSON data, avoiding PHP object serialization
 * issues like __PHP_Incomplete_Class.
 */
class CachedLocation implements Location {

	private ?Coordinates $coordinates = null;

	private ?Bounds $bounds = null;

	private string|int|null $streetNumber = null;

	private ?string $streetName = null;

	private ?string $subLocality = null;

	private ?string $locality = null;

	private ?string $postalCode = null;

	private AdminLevelCollection $adminLevels;

	private ?Country $country = null;

	private ?string $timezone = null;

	private string $providedBy = 'cached';

	/**
	 * @param array<string, mixed> $data
	 */
	public function __construct(array $data = []) {
		$this->adminLevels = new AdminLevelCollection();

		if (!empty($data)) {
			$this->fromArray($data);
		}
	}

	/**
	 * Create a CachedLocation from array data (e.g., from JSON storage).
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return static
	 */
	public static function createFromArray(array $data): static {
		return new static($data);
	}

	/**
	 * Populate this location from array data.
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	protected function fromArray(array $data): void {
		if (isset($data['providedBy'])) {
			$this->providedBy = (string)$data['providedBy'];
		}

		if (isset($data['latitude'], $data['longitude'])) {
			$this->coordinates = new Coordinates(
				(float)$data['latitude'],
				(float)$data['longitude'],
			);
		}

		if (isset($data['bounds']['south'], $data['bounds']['west'], $data['bounds']['north'], $data['bounds']['east'])) {
			$this->bounds = new Bounds(
				(float)$data['bounds']['south'],
				(float)$data['bounds']['west'],
				(float)$data['bounds']['north'],
				(float)$data['bounds']['east'],
			);
		}

		$this->streetNumber = $data['streetNumber'] ?? null;
		$this->streetName = $data['streetName'] ?? null;
		$this->locality = $data['locality'] ?? null;
		$this->postalCode = $data['postalCode'] ?? null;
		$this->subLocality = $data['subLocality'] ?? null;
		$this->timezone = $data['timezone'] ?? null;

		if (isset($data['country']) || isset($data['countryCode'])) {
			$this->country = new Country(
				$data['country'] ?? null,
				$data['countryCode'] ?? null,
			);
		}

		if (!empty($data['adminLevels']) && is_array($data['adminLevels'])) {
			$levels = [];
			foreach ($data['adminLevels'] as $level) {
				if (isset($level['level'])) {
					$levels[] = new AdminLevel(
						(int)$level['level'],
						$level['name'] ?? '',
						$level['code'] ?? null,
					);
				}
			}
			$this->adminLevels = new AdminLevelCollection($levels);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getCoordinates(): ?Coordinates {
		return $this->coordinates;
	}

	/**
	 * @inheritDoc
	 */
	public function getBounds(): ?Bounds {
		return $this->bounds;
	}

	/**
	 * @inheritDoc
	 */
	public function getStreetNumber(): string|int|null {
		return $this->streetNumber;
	}

	/**
	 * @inheritDoc
	 */
	public function getStreetName(): ?string {
		return $this->streetName;
	}

	/**
	 * @inheritDoc
	 */
	public function getLocality(): ?string {
		return $this->locality;
	}

	/**
	 * @inheritDoc
	 */
	public function getPostalCode(): ?string {
		return $this->postalCode;
	}

	/**
	 * @inheritDoc
	 */
	public function getSubLocality(): ?string {
		return $this->subLocality;
	}

	/**
	 * @inheritDoc
	 */
	public function getAdminLevels(): AdminLevelCollection {
		return $this->adminLevels;
	}

	/**
	 * @inheritDoc
	 */
	public function getCountry(): ?Country {
		return $this->country;
	}

	/**
	 * @inheritDoc
	 */
	public function getTimezone(): ?string {
		return $this->timezone;
	}

	/**
	 * @inheritDoc
	 */
	public function toArray(): array {
		$adminLevels = [];
		foreach ($this->adminLevels as $adminLevel) {
			$adminLevels[] = [
				'level' => $adminLevel->getLevel(),
				'name' => $adminLevel->getName(),
				'code' => $adminLevel->getCode(),
			];
		}

		$country = null;
		$countryCode = null;
		if ($this->country !== null) {
			$country = $this->country->getName();
			$countryCode = $this->country->getCode();
		}

		$bounds = null;
		if ($this->bounds !== null) {
			$bounds = [
				'south' => $this->bounds->getSouth(),
				'west' => $this->bounds->getWest(),
				'north' => $this->bounds->getNorth(),
				'east' => $this->bounds->getEast(),
			];
		}

		return [
			'providedBy' => $this->providedBy,
			'latitude' => $this->coordinates?->getLatitude(),
			'longitude' => $this->coordinates?->getLongitude(),
			'bounds' => $bounds,
			'streetNumber' => $this->streetNumber,
			'streetName' => $this->streetName,
			'postalCode' => $this->postalCode,
			'locality' => $this->locality,
			'subLocality' => $this->subLocality,
			'adminLevels' => $adminLevels,
			'country' => $country,
			'countryCode' => $countryCode,
			'timezone' => $this->timezone,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getProvidedBy(): string {
		return $this->providedBy;
	}

}
