<?php

namespace TearoomOne\LeafletMap\Fields;

use Kirby\Data\Data;
use Kirby\Form\Field\InputField;

/**
 * Coordinate picker: click on the map or drag the marker.
 * Stores `lat` and `lng`, or nothing while no location is set.
 */
class LocationField extends InputField
{
    use MapProps;

    protected array|null $value = null;

    public function fill(mixed $value): static
    {
        return parent::fill(
            value: static::toLocation($value)
        );
    }

    /**
     * Normalizes a stored or submitted value to
     * `['lat' => float, 'lng' => float]` or `null`
     */
    public static function toLocation(mixed $value): array|null
    {
        $value = is_array($value) ? $value : Data::decode($value, 'yaml', fail: false);

        return is_numeric($value['lat'] ?? null) && is_numeric($value['lng'] ?? null)
            ? ['lat' => (float)$value['lat'], 'lng' => (float)$value['lng']]
            : null;
    }
}
