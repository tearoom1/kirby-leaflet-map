<?php

namespace TearoomOne\LeafletMap;

use Kirby\Cms\Block;
use Kirby\Cms\StructureObject;

class Utils
{
    public const DEFAULT_TILES_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    public const DEFAULT_TILES_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

    static function printAsset($page, $assetType): void
    {
        if (!option('tearoom1.leaflet-map.enabled', true)) {
            return;
        }
        if (option('tearoom1.leaflet-map.alwaysIncludeAssets', true)) {
            self::printAssetString($assetType);
            return;
        }
        // otherwise check if there is a leaflet map block present on the page
        $fieldsToCheck = array_keys(array_filter($page->blueprint()->fields(),
            fn($item) => in_array($item['type'], ['blocks', 'layout'])));
        foreach ($fieldsToCheck as $fieldName) {
            if ($page->{$fieldName}()->toBlocks()->hasType('leaflet-map')) {
                self::printAssetString($assetType);
                break;
            }
        }
    }

    /**
     * Tile layer settings shared by the website and the panel picker.
     */
    public static function tiles(): array
    {
        return [
            'url'         => option('tearoom1.leaflet-map.tiles.url', self::DEFAULT_TILES_URL),
            'attribution' => option('tearoom1.leaflet-map.tiles.attribution', self::DEFAULT_TILES_ATTRIBUTION),
            'maxZoom'     => (int)option('tearoom1.leaflet-map.tiles.maxZoom', 19),
        ];
    }

    /**
     * Prepare the data the frontend script needs, with validated types.
     */
    public static function mapData(Block $block): array
    {
        $locations = [];
        foreach ($block->mapItems()->toStructure() as $item) {
            $coordinates = self::coordinates($item);
            if ($coordinates === null || $item->hide()->toBool()) {
                continue;
            }

            $locations[] = [
                'lat'         => $coordinates['lat'],
                'lng'         => $coordinates['lng'],
                'title'       => $item->title()->value() ?? '',
                'description' => $item->description()->isNotEmpty() ? $item->description()->kti()->value() : '',
                'size'        => max(1, min(5, $item->size()->toInt() ?: 1)),
                'color'       => self::color($item->color()->value(), '#3388ff'),
                'tooltip'     => $item->tooltip()->toBool(),
                'center'      => $item->center()->toBool(),
            ];
        }

        $paths = [];
        foreach ($block->paths()->toStructure() as $path) {
            $points = [];
            // the path editor stores a list of points, older content a points structure
            $line = $path->line()->yaml();
            if ($line !== []) {
                foreach ($line as $point) {
                    if (is_numeric($point['lat'] ?? null) && is_numeric($point['lng'] ?? null)) {
                        $points[] = [(float)$point['lat'], (float)$point['lng']];
                    }
                }
            } else {
                foreach ($path->points()->toStructure() as $point) {
                    if (($coordinates = self::coordinates($point)) !== null) {
                        $points[] = [$coordinates['lat'], $coordinates['lng']];
                    }
                }
            }

            if (count($points) < 2) {
                continue;
            }

            $paths[] = [
                'title'   => $path->title()->value() ?? '',
                'color'   => self::color($path->color()->value(), '#3388ff'),
                'tooltip' => $path->tooltip()->toBool(),
                'points'  => $points,
            ];
        }

        $minZoom = $block->minZoom()->toInt() ?: 10;
        $maxZoom = $block->maxZoom()->toInt() ?: 19;
        [$minZoom, $maxZoom] = [min($minZoom, $maxZoom), max($minZoom, $maxZoom)];

        $tiles = self::tiles();
        if (TileProxy::enabled()) {
            // the token limits the proxy to the area of this map
            $points = array_map(fn ($location) => [$location['lat'], $location['lng']], $locations);
            foreach ($paths as $path) {
                $points = array_merge($points, $path['points']);
            }
            // zoom 0 upwards: the map may zoom out further to show all locations
            $tiles['url'] = TileProxy::urlTemplate($points, 0, $maxZoom);
        }

        $labels = $block->labels()->value();

        return [
            // individual: per location toggle, always, hover or none
            'labels'    => in_array($labels, ['always', 'hover', 'none'], true) ? $labels : 'individual',
            'locations' => $locations,
            'paths'     => $paths,
            'zoom'      => [
                'default' => $block->defaultZoom()->toInt() ?: 15,
                'min'     => $minZoom,
                'max'     => $maxZoom,
            ],
            'tiles'     => $tiles,
        ];
    }

    /**
     * Read coordinates from the location picker field or the former lat/lng fields.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function coordinates(StructureObject $item): ?array
    {
        $location = $item->location()->yaml();
        $lat = $location['lat'] ?? $item->lat()->value();
        $lng = $location['lng'] ?? $item->lng()->value();

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float)$lat;
        $lng = (float)$lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Only allow hex colors, so the value is safe for Leaflet's style options.
     */
    public static function color(?string $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1 ? $value : $fallback;
    }

    /**
     * JSON that can be embedded in a script tag without breaking out of it.
     */
    public static function json(array $data): string
    {
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $assetType
     * @return void
     */
    private static function printAssetString($assetType): void
    {
        if ($assetType === 'css') {
            echo css(['media/plugins/tearoom1/leaflet-map/css/leaflet-map.css']);
        } elseif ($assetType === 'js') {
            echo js(['media/plugins/tearoom1/leaflet-map/js/leaflet.js']);
            echo js(['media/plugins/tearoom1/leaflet-map/js/leaflet-map.js']);
        }
    }
}
