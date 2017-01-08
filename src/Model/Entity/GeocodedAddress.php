<?php
namespace Geo\Model\Entity;

use Cake\ORM\Entity;

/**
 * GeocodedAddress Entity.
 *
 * @property int $id
 * @property string $address
 * @property string $formatted_address
 * @property string $country
 * @property float $lat
 * @property float $lng
 * @property string $data
 */
class GeocodedAddress extends Entity {

	/**
	 * Fields that can be mass assigned using newEntity() or patchEntity().
	 *
	 * Note that when '*' is set to true, this allows all unspecified fields to
	 * be mass assigned. For security purposes, it is advised to set '*' to false
	 * (or remove it), and explicitly make individual fields accessible as needed.
	 *
	 * @var array
	 */
	protected $_accessible = [
		'*' => true,
		'id' => false,
	];

}
