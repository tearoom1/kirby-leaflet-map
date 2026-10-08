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
     * Map styles that can be enabled with the `layers` option. All of them are
     * free OpenStreetMap based tile services; check their usage policies
     * before using them on a busy site, or use the tile proxy.
     */
    public const LAYER_PRESETS = [
        'osm' => [
            'label'       => 'OpenStreetMap',
            'url'         => self::DEFAULT_TILES_URL,
            'attribution' => self::DEFAULT_TILES_ATTRIBUTION,
            'maxZoom'     => 19,
        ],
        'osm-de' => [
            'label'       => 'OpenStreetMap Deutschland',
            'url'         => 'https://tile.openstreetmap.de/{z}/{x}/{y}.png',
            'attribution' => self::DEFAULT_TILES_ATTRIBUTION,
            'maxZoom'     => 18,
        ],
        'humanitarian' => [
            'label'       => 'Humanitarian',
            'url'         => 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
            'attribution' => self::DEFAULT_TILES_ATTRIBUTION . ', Tiles style by <a href="https://www.hotosm.org/">Humanitarian OpenStreetMap Team</a> hosted by <a href="https://openstreetmap.fr/">OpenStreetMap France</a>',
            'maxZoom'     => 19,
        ],
        'topo' => [
            'label'       => 'OpenTopoMap',
            'url'         => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
            'attribution' => 'Map data: ' . self::DEFAULT_TILES_ATTRIBUTION . ', SRTM | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
            'maxZoom'     => 17,
        ],
        'cyclosm' => [
            'label'       => 'CyclOSM',
            'url'         => 'https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png',
            'attribution' => '<a href="https://www.cyclosm.org">CyclOSM</a> hosted by <a href="https://openstreetmap.fr/">OpenStreetMap France</a> | Map data: ' . self::DEFAULT_TILES_ATTRIBUTION,
            'maxZoom'     => 20,
        ],
    ];

    /**
     * Tile layer settings of the `tiles` option, the default map style.
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
     * The map styles editors can choose from, the first one is the default.
     * The `layers` option lists preset names, 'default' for the `tiles`
     * option, or own definitions: 'name' => ['label', 'url', 'attribution', 'maxZoom'].
     *
     * @return array<string, array{label: string, url: string, attribution: string, maxZoom: int}>
     */
    public static function layers(): array
    {
        $layers = [];
        foreach ((array)option('tearoom1.leaflet-map.layers', ['default']) as $key => $layer) {
            if (is_string($layer)) {
                [$key, $layer] = [$layer, $layer === 'default'
                    ? ['label' => 'Standard'] + self::tiles()
                    : self::LAYER_PRESETS[$layer] ?? null];
            }

            if (is_array($layer) && is_string($layer['url'] ?? null)) {
                $layers[(string)$key] = [
                    'label'       => (string)($layer['label'] ?? $key),
                    'url'         => $layer['url'],
                    'attribution' => (string)($layer['attribution'] ?? ''),
                    'maxZoom'     => (int)($layer['maxZoom'] ?? 19),
                ];
            }
        }

        return $layers !== [] ? $layers : ['default' => ['label' => 'Standard'] + self::tiles()];
    }

    /**
     * Settings of a map style, the default one for unknown names.
     */
    public static function layer(?string $key): array
    {
        $layers = self::layers();

        return $layers[$key ?? ''] ?? reset($layers);
    }

    /**
     * Key of the map style, null for unknown names.
     */
    public static function layerKey(?string $key): ?string
    {
        return $key !== null && isset(self::layers()[$key]) ? $key : null;
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

            $color = self::color($item->color()->value(), '#3388ff');
            $icon  = $item->icon()->value();

            $locations[] = [
                'lat'         => $coordinates['lat'],
                'lng'         => $coordinates['lng'],
                'title'       => $item->title()->value() ?? '',
                'description' => $item->description()->isNotEmpty() ? $item->description()->kti()->value() : '',
                'size'        => max(1, min(5, $item->size()->toInt() ?: 1)),
                'color'       => $color,
                'icon'        => Icons::exists($icon) ? $icon : null,
                'iconColor'   => self::contrast($color),
                'category'    => trim($item->category()->value() ?? ''),
                'tooltip'     => $item->tooltip()->toBool(),
                'center'      => $item->center()->toBool(),
            ];
        }

        // each symbol once, the markers refer to it by name
        $icons = [];
        foreach (array_filter(array_column($locations, 'icon')) as $icon) {
            $icons[$icon] ??= Icons::svg($icon);
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
                'title'    => $path->title()->value() ?? '',
                'category' => trim($path->category()->value() ?? ''),
                'color'    => self::color($path->color()->value(), '#3388ff'),
                'tooltip' => $path->tooltip()->toBool(),
                'points'  => $points,
            ];
        }

        $minZoom = $block->minZoom()->toInt() ?: 10;
        $maxZoom = $block->maxZoom()->toInt() ?: 19;
        [$minZoom, $maxZoom] = [min($minZoom, $maxZoom), max($minZoom, $maxZoom)];

        // the selected map style first, the others only if visitors may switch
        $selected = self::layerKey($block->layer()->value()) ?? array_key_first(self::layers());
        $keys = [$selected];
        if ($block->layerSwitch()->toBool()) {
            $keys = array_unique([$selected, ...array_keys(self::layers())]);
        }

        $points = array_map(fn ($location) => [$location['lat'], $location['lng']], $locations);
        foreach ($paths as $path) {
            $points = array_merge($points, $path['points']);
        }

        $layers = [];
        foreach ($keys as $key) {
            $layer = self::layer($key);
            if (TileProxy::enabled()) {
                // the token limits the proxy to the area of this map
                // zoom 0 upwards: the map may zoom out further to show all locations
                $layer['url'] = TileProxy::urlTemplate($points, 0, min($maxZoom, $layer['maxZoom']), $key);
            }
            $layers[] = $layer;
        }

        $labels = $block->labels()->value();

        return [
            // individual: per location toggle, always, hover or none
            'labels'    => in_array($labels, ['always', 'hover', 'none'], true) ? $labels : 'individual',
            'locations' => $locations,
            'paths'     => $paths,
            'icons'     => $icons,
            'zoom'      => [
                'default' => $block->defaultZoom()->toInt() ?: 15,
                'min'     => $minZoom,
                'max'     => $maxZoom,
            ],
            'tiles'     => $layers[0],
            'layers'    => count($layers) > 1 ? $layers : [],
        ];
    }

    /**
     * Legend entries: locations and paths grouped by their legend entry.
     * Without an entry, locations are grouped by symbol, plain markers and
     * paths each share one general line, so the legend stays short.
     *
     * Colors may differ within a group, so the legend shows them only for
     * own legend entries whose locations or paths all share one color.
     * Otherwise the color is null and the legend shows a neutral symbol.
     *
     * @return array<int, array{label: string, color: ?string, icon: ?string, iconColor: string, path: bool}>
     */
    public static function legend(array $mapData): array
    {
        $groups = [];
        foreach ($mapData['locations'] as $location) {
            $label = match (true) {
                $location['category'] !== '' => $location['category'],
                $location['icon'] !== null   => Icons::label($location['icon']),
                default                      => t('tearoom1.leaflet-map.legend.location', 'Location'),
            };

            $groups[$label] ??= [
                'label'    => $label,
                'icon'     => $location['icon'] !== null ? $mapData['icons'][$location['icon']] : null,
                'path'     => false,
                'explicit' => $location['category'] !== '',
                'colors'   => [],
            ];
            $groups[$label]['colors'][$location['color']] = true;
        }

        foreach ($mapData['paths'] as $path) {
            $label = $path['category'] !== '' ? $path['category'] : t('tearoom1.leaflet-map.legend.path', 'Path');

            // a separate key, so a path and a location may share a label
            $groups['path:' . $label] ??= [
                'label'    => $label,
                'icon'     => null,
                'path'     => true,
                'explicit' => $path['category'] !== '',
                'colors'   => [],
            ];
            $groups['path:' . $label]['colors'][$path['color']] = true;
        }

        return array_values(array_map(function (array $group) {
            $color = $group['explicit'] && count($group['colors']) === 1 ? array_key_first($group['colors']) : null;

            return [
                'label'     => $group['label'],
                'color'     => $color,
                'icon'      => $group['icon'],
                'iconColor' => $color !== null ? self::contrast($color) : '#ffffff',
                'path'      => $group['path'],
            ];
        }, $groups));
    }

    /**
     * Dark symbols on light marker colors, white ones otherwise.
     */
    public static function contrast(string $color): string
    {
        $hex = ltrim($color, '#');
        if (strlen($hex) < 6) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        [$r, $g, $b] = array_map(fn ($part) => hexdec($part) / 255, str_split(substr($hex, 0, 6), 2));
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $luminance > 0.6 ? '#1d1d1d' : '#ffffff';
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
