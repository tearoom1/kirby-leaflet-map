<?php

namespace TearoomOne\LeafletMap\Fields;

use Kirby\Data\Data;
use Kirby\Form\Field\InputField;

/**
 * Path editor: click on the map to add points, drag or click them to edit.
 * Stores a list of points, or nothing while the path is empty.
 */
class PathField extends InputField
{
    use MapProps;

    protected array $value = [];

    public function fill(mixed $value): static
    {
        return parent::fill(
            value: static::toPoints($value)
        );
    }

    /**
     * Normalizes a stored or submitted value to a list of
     * `['lat' => float, 'lng' => float]`, skipping invalid points
     */
    public static function toPoints(mixed $value): array
    {
        $value = is_array($value) ? $value : Data::decode($value, 'yaml', fail: false);

        return array_values(array_filter(array_map(
            fn ($point) => is_numeric($point['lat'] ?? null) && is_numeric($point['lng'] ?? null)
                ? ['lat' => (float)$point['lat'], 'lng' => (float)$point['lng']]
                : null,
            $value
        )));
    }

    /**
     * An empty path is stored as nothing, as before
     */
    public function toStoredValue(): array|null
    {
        return $this->value === [] ? null : $this->value;
    }
}
