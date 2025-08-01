<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('tearoom1/kirby-leaflet-map', [
    'blueprints' => [
        'blocks/leaflet-map' => __DIR__ . '/blueprints/blocks/leaflet-map.yml'
    ],
    'snippets' => [
        'blocks/leaflet-map' => __DIR__ . '/snippets/blocks/leaflet-map.php',
        'leaflet-map/css' => __DIR__ . '/snippets/css.php',
        'leaflet-map/js' => __DIR__ . '/snippets/js.php',
    ],
]);


