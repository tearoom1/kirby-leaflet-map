<?php

namespace TearoomOne\LeafletMap\Fields;

use Kirby\Form\Field\InputField;
use TearoomOne\LeafletMap\Icons;

/**
 * Marker symbol, shown on the color of the location.
 * Stores the name of the symbol, or nothing for no symbol.
 */
class IconField extends InputField
{
    protected string|null $value = null;

    public function fill(mixed $value): static
    {
        // unknown symbols (e.g. removed with the `icons` option) are dropped
        return parent::fill(
            value: is_string($value) && Icons::exists($value) ? $value : null
        );
    }

    /**
     * All available symbols with their label and SVG
     */
    public function icons(): array
    {
        return Icons::options();
    }

    public function props(): array
    {
        return [
            ...parent::props(),
            'icons' => $this->icons(),
        ];
    }
}
