<?php
declare(strict_types=1);

namespace Geo\StaticMap\Provider;

/**
 * Interface for static map providers.
 *
 * Each provider implements URL building for their specific static map API.
 */
interface StaticMapProviderInterface {

	/**
	 * Get the provider name.
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Build the URL for the static map.
	 *
	 * @param array<string, mixed> $options Map options (lat, lng, zoom, size, style, etc.)
	 * @param array<array<string, mixed>> $markers Array of marker definitions
	 * @param array<array<string, mixed>> $paths Array of path definitions
	 * @return string The complete URL for the static map image
	 */
	public function buildUrl(array $options, array $markers = [], array $paths = []): string;

	/**
	 * Check if this provider requires an API key.
	 *
	 * @return bool
	 */
	public function requiresApiKey(): bool;

	/**
	 * Get supported map styles for this provider.
	 *
	 * @return array<string>
	 */
	public function getSupportedStyles(): array;

}
