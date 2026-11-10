<?php

namespace TearoomOne\LeafletMap\Fields;

use Kirby\Reflection\Attributes\Prop;
use TearoomOne\LeafletMap\Geocoder;
use TearoomOne\LeafletMap\TileProxy;
use TearoomOne\LeafletMap\Utils;

/**
 * Shared options of the panel picker maps
 */
trait MapProps
{
    /**
     * Map center while no value is set: [lat, lng]
     */
    #[Prop]
    protected array|null $center = null;

    /**
     * Zoom level while no value is set
     */
    #[Prop]
    protected int|null $zoom = null;

    public function center(): array
    {
        return $this->center ?? option('tearoom1.leaflet-map.panel.center', [20, 0]);
    }

    public function zoom(): int
    {
        return $this->zoom ?? option('tearoom1.leaflet-map.panel.zoom', 2);
    }

    /**
     * Whether the address search is available
     */
    public function search(): bool
    {
        return Geocoder::enabled();
    }

    /**
     * Tile options for the panel map, through the proxy if it is enabled
     */
    public function tiles(): array
    {
        return TileProxy::enabled()
            ? ['url' => TileProxy::panelUrlTemplate()] + Utils::tiles()
            : Utils::tiles();
    }

    /**
     * Adds the computed options, which can't be set in the blueprint
     */
    public function props(): array
    {
        return [
            ...parent::props(),
            'search' => $this->search(),
            'tiles'  => $this->tiles(),
        ];
    }
}
