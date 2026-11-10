<?php

load([
    'TearoomOne\\LeafletMap\\Utils' => 'src/Utils.php',
    'TearoomOne\\LeafletMap\\TileProxy' => 'src/TileProxy.php',
    'TearoomOne\\LeafletMap\\Geocoder' => 'src/Geocoder.php',
    'TearoomOne\\LeafletMap\\Icons' => 'src/Icons.php',
    'TearoomOne\\LeafletMap\\Fields\\MapProps' => 'src/Fields/MapProps.php',
    'TearoomOne\\LeafletMap\\Fields\\IconField' => 'src/Fields/IconField.php',
    'TearoomOne\\LeafletMap\\Fields\\LocationField' => 'src/Fields/LocationField.php',
    'TearoomOne\\LeafletMap\\Fields\\PathField' => 'src/Fields/PathField.php',
], __DIR__);

use Kirby\Data\Yaml;
use TearoomOne\LeafletMap\Fields\IconField;
use TearoomOne\LeafletMap\Fields\LocationField;
use TearoomOne\LeafletMap\Fields\PathField;
use TearoomOne\LeafletMap\Geocoder;
use TearoomOne\LeafletMap\TileProxy;
use TearoomOne\LeafletMap\Utils;

Kirby::plugin('tearoom1/leaflet-map', [
    'options' => [
        'cache.geocode' => true,
    ],
    'blueprints' => [
        'blocks/leaflet-map' => function () {
            $blueprint = Yaml::read(__DIR__ . '/blueprints/blocks/leaflet-map.yml');
            $layers = Utils::layers();

            // the choice of map styles only appears if the site offers several
            if (count($layers) > 1) {
                $fields = [];
                foreach ($blueprint['tabs']['content']['fields'] as $name => $field) {
                    $fields[$name] = $field;
                    if ($name === 'legend') {
                        $fields['layer'] = [
                            'label'    => ['en' => 'Map Style', 'de' => 'Kartenstil'],
                            'type'        => 'select',
                            'width'       => '1/2',
                            // empty: the first style, also for maps created before the option was set
                            'placeholder' => reset($layers)['label'],
                            'options'     => array_map(fn ($layer) => $layer['label'], $layers),
                        ];
                        $fields['layerSwitch'] = [
                            'label' => ['en' => 'Switch styles', 'de' => 'Stil wechseln'],
                            'type'  => 'toggle',
                            'width' => '1/2',
                            'text'  => ['en' => 'Visitors can switch the map style', 'de' => 'Besucher können den Kartenstil wechseln'],
                        ];
                    }
                }
                $blueprint['tabs']['content']['fields'] = $fields;
            }

            return $blueprint;
        },
    ],
    'snippets' => [
        'blocks/leaflet-map' => __DIR__ . '/snippets/blocks/leaflet-map.php',
        'leaflet-map/css' => __DIR__ . '/snippets/css.php',
        'leaflet-map/js' => __DIR__ . '/snippets/js.php',
        'leaflet-map/legend' => __DIR__ . '/snippets/legend.php',
    ],
    'fields' => [
        // Coordinate picker: click on the map or drag the marker
        'leaflet-location' => LocationField::class,
        // Marker symbol, shown on the color of the location
        'leaflet-icon' => IconField::class,
        // Path editor: click on the map to add points, drag or click them to edit
        'leaflet-path' => PathField::class,
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
            'tearoom1.leaflet-map.icon.none' => 'No symbol',
            'tearoom1.leaflet-map.legend' => 'Legend',
            'tearoom1.leaflet-map.legend.location' => 'Location',
            'tearoom1.leaflet-map.legend.path' => 'Path',
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
            'tearoom1.leaflet-map.icon.none' => 'Kein Symbol',
            'tearoom1.leaflet-map.legend' => 'Legende',
            'tearoom1.leaflet-map.legend.location' => 'Ort',
            'tearoom1.leaflet-map.legend.path' => 'Weg',
        ],
    ],
]);
