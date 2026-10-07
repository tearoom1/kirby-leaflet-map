<?php

/**
 * Stand-in for the tile server and Nominatim, served with `php -S`.
 * Every request is appended to the file in MOCK_LOG.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
file_put_contents(getenv('MOCK_LOG'), $_SERVER['REQUEST_URI'] . ' | ' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n", FILE_APPEND);

// tiles: /tiles/{z}/{x}/{y}.png
if (preg_match('#^/tiles/(\d+)/(\d+)/(\d+)\.png$#', $path, $matches)) {
    header('Content-Type: image/png');
    echo 'PNG ' . $matches[1] . '/' . $matches[2] . '/' . $matches[3];
    return;
}

if ($path === '/tiles-html/1/1/1.png') {
    header('Content-Type: text/html');
    echo '<h1>Blocked</h1>';
    return;
}

// geocoding: /search?q=...
if ($path === '/search') {
    header('Content-Type: application/json');
    echo json_encode([
        ['display_name' => 'Marienplatz, München', 'lat' => '48.1371540', 'lon' => '11.5761240'],
        ['display_name' => 'Marienplatz, München', 'lat' => '48.1371', 'lon' => '11.5761'],
        ['display_name' => 'No coordinates'],
        ['display_name' => 'Marienplatz, Bielefeld', 'lat' => '52.0', 'lon' => '8.5'],
    ]);
    return;
}

http_response_code(404);
