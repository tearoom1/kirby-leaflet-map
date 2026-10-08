<?php

load([
    'TearoomOne\\LeafletMap\\Utils' => 'src/Utils.php',
    'TearoomOne\\LeafletMap\\TileProxy' => 'src/TileProxy.php',
    'TearoomOne\\LeafletMap\\Geocoder' => 'src/Geocoder.php',
], __DIR__);

use Kirby\Data\Data;
use TearoomOne\LeafletMap\Geocoder;
use TearoomOne\LeafletMap\TileProxy;
use TearoomOne\LeafletMap\Utils;

Kirby::plugin('tearoom1/leaflet-map', [
    'options' => [
        'cache.geocode' => true,
    ],
    'blueprints' => [
        'blocks/leaflet-map' => __DIR__ . '/blueprints/blocks/leaflet-map.yml'
    ],
    'snippets' => [
        'blocks/leaflet-map' => __DIR__ . '/snippets/blocks/leaflet-map.php',
        'leaflet-map/css' => __DIR__ . '/snippets/css.php',
        'leaflet-map/js' => __DIR__ . '/snippets/js.php',
    ],
    'fields' => [
        // Coordinate picker: click on the map or drag the marker
        'leaflet-location' => [
            'props' => [
                'value' => function ($value = null) {
                    $value = is_array($value) ? $value : Data::decode($value, 'yaml');

                    return is_numeric($value['lat'] ?? null) && is_numeric($value['lng'] ?? null)
                        ? ['lat' => (float)$value['lat'], 'lng' => (float)$value['lng']]
                        : null;
                },
                // Map center while no location is set: [lat, lng]
                'center' => fn (array|null $center = null) => $center ?? option('tearoom1.leaflet-map.panel.center', [20, 0]),
                'zoom' => fn (int|null $zoom = null) => $zoom ?? option('tearoom1.leaflet-map.panel.zoom', 2),
            ],
            'computed' => [
                'search' => fn () => Geocoder::enabled(),
                'tiles' => fn () => TileProxy::enabled()
                    ? ['url' => TileProxy::panelUrlTemplate()] + Utils::tiles()
                    : Utils::tiles(),
            ],
            'save' => fn ($value) => is_array($value) && isset($value['lat'], $value['lng']) ? $value : null,
        ],
        // Path editor: click on the map to add points, drag or click them to edit
        'leaflet-path' => [
            'props' => [
                'value' => function ($value = null) {
                    $value = is_array($value) ? $value : Data::decode($value, 'yaml');

                    return array_values(array_filter(array_map(
                        fn ($point) => is_numeric($point['lat'] ?? null) && is_numeric($point['lng'] ?? null)
                            ? ['lat' => (float)$point['lat'], 'lng' => (float)$point['lng']]
                            : null,
                        is_array($value) ? $value : []
                    )));
                },
                'center' => fn (array|null $center = null) => $center ?? option('tearoom1.leaflet-map.panel.center', [20, 0]),
                'zoom' => fn (int|null $zoom = null) => $zoom ?? option('tearoom1.leaflet-map.panel.zoom', 2),
            ],
            'computed' => [
                'search' => fn () => Geocoder::enabled(),
                'tiles' => fn () => TileProxy::enabled()
                    ? ['url' => TileProxy::panelUrlTemplate()] + Utils::tiles()
                    : Utils::tiles(),
            ],
            'save' => fn ($value) => is_array($value) && $value !== [] ? array_values($value) : null,
        ],
    ],
    'api' => [
        'routes' => [
            [
                // address search for the picker, panel users only (API auth)
                'pattern' => 'leaflet-map/geocode',
                'method' => 'GET',
                'action' => fn () => Geocoder::search(
                    (string)get('q', ''),
                    kirby()->user()?->language()
                ),
            ],
        ],
    ],
    'routes' => [
        [
            // only reached on a cache miss, afterwards the web server delivers the stored file
            'pattern' => TileProxy::ROUTE . '/(:any)/(:num)/(:num)/(:num).png',
            'action' => fn (string $token, string $z, string $x, string $y) => TileProxy::serve($token, (int)$z, (int)$x, (int)$y),
        ],
    ],
    'translations' => [
        'en' => [
            'tearoom1.leaflet-map.map' => 'Map',
            'tearoom1.leaflet-map.open' => 'View interactive map',
            'tearoom1.leaflet-map.close' => 'Close map',
            'tearoom1.leaflet-map.load' => 'Load map',
            'tearoom1.leaflet-map.consent' => 'The map is loaded from an external server. Your IP address will be transmitted to it.',
            'tearoom1.leaflet-map.location.empty' => 'Click on the map to set the location',
            'tearoom1.leaflet-map.location.clear' => 'Remove location',
            'tearoom1.leaflet-map.location.search' => 'Search address …',
            'tearoom1.leaflet-map.location.noResults' => 'No results',
            'tearoom1.leaflet-map.path.points' => 'points',
            'tearoom1.leaflet-map.path.help' => 'Click on the map to add a point, drag a point to move it, click it to remove it',
            'tearoom1.leaflet-map.path.undo' => 'Remove last',
            'tearoom1.leaflet-map.path.clear' => 'Remove all points',
            'tearoom1.leaflet-map.path.remove' => 'Click to remove, drag to move',
        ],
        'de' => [
            'tearoom1.leaflet-map.map' => 'Karte',
            'tearoom1.leaflet-map.open' => 'Interaktive Karte öffnen',
            'tearoom1.leaflet-map.close' => 'Karte schließen',
            'tearoom1.leaflet-map.load' => 'Karte laden',
            'tearoom1.leaflet-map.consent' => 'Die Karte wird von einem externen Server geladen. Dabei wird deine IP-Adresse übertragen.',
            'tearoom1.leaflet-map.location.empty' => 'Klicke in die Karte, um den Ort zu setzen',
            'tearoom1.leaflet-map.location.clear' => 'Ort entfernen',
            'tearoom1.leaflet-map.location.search' => 'Adresse suchen …',
            'tearoom1.leaflet-map.location.noResults' => 'Keine Treffer',
            'tearoom1.leaflet-map.path.points' => 'Punkte',
            'tearoom1.leaflet-map.path.help' => 'Klicke in die Karte, um einen Punkt anzuhängen, ziehe einen Punkt, um ihn zu verschieben, klicke ihn an, um ihn zu entfernen',
            'tearoom1.leaflet-map.path.undo' => 'Letzten entfernen',
            'tearoom1.leaflet-map.path.clear' => 'Alle Punkte entfernen',
            'tearoom1.leaflet-map.path.remove' => 'Klicken zum Entfernen, ziehen zum Verschieben',
        ],
    ],
]);
