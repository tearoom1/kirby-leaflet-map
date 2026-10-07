<?php

namespace TearoomOne\LeafletMap;

use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Http\Remote;
use Kirby\Http\Response;

/**
 * Serves map tiles from the own server, so visitors never contact the tile
 * provider directly. Each map gets a signed token that limits the proxy to
 * its own area and zoom levels; fetched tiles are stored in the media folder
 * and from then on delivered by the web server without PHP.
 */
class TileProxy
{
    public const ROUTE = 'media/leaflet-map/tiles';

    /** Tiles allowed around the map area, so panning a bit still works */
    public const MARGIN_TILES = 8;

    /** Token for the panel picker: the whole world, logged-in users only */
    public const PANEL_TOKEN = 'panel';

    public static function enabled(): bool
    {
        return option('tearoom1.leaflet-map.tiles.proxy', false) === true;
    }

    /**
     * Tile URL template for a map covering the given points.
     *
     * @param array<int, array{0: float, 1: float}> $points [lat, lng] pairs
     */
    public static function urlTemplate(array $points, int $minZoom, int $maxZoom): string
    {
        return self::baseUrl() . '/' . self::token($points, $minZoom, $maxZoom) . '/{z}/{x}/{y}.png';
    }

    public static function panelUrlTemplate(): string
    {
        return self::baseUrl() . '/' . self::PANEL_TOKEN . '/{z}/{x}/{y}.png';
    }

    public static function token(array $points, int $minZoom, int $maxZoom): string
    {
        if ($points === []) {
            $points = [[0.0, 0.0]];
        }

        $lats = array_column($points, 0);
        $lngs = array_column($points, 1);

        $payload = implode(',', [
            // the period renews the tile cache every month
            date('Ym'),
            round(min($lats), 5),
            round(min($lngs), 5),
            round(max($lats), 5),
            round(max($lngs), 5),
            $minZoom,
            $maxZoom,
        ]);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . self::sign($payload);
    }

    /**
     * Decode and verify a map token.
     *
     * @return array{period: string, south: float, west: float, north: float, east: float, minZoom: int, maxZoom: int}|null
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        $payload = base64_decode(strtr($parts[0], '-_', '+/'), true);
        if ($payload === false || hash_equals(self::sign($payload), $parts[1]) === false) {
            return null;
        }

        $values = explode(',', $payload);
        if (count($values) !== 7) {
            return null;
        }

        [$period, $south, $west, $north, $east, $minZoom, $maxZoom] = $values;

        return [
            'period'  => $period,
            'south'   => (float)$south,
            'west'    => (float)$west,
            'north'   => (float)$north,
            'east'    => (float)$east,
            'minZoom' => (int)$minZoom,
            'maxZoom' => (int)$maxZoom,
        ];
    }

    /**
     * Whether the tile may be served for this token.
     */
    public static function allows(string $token, int $z, int $x, int $y): bool
    {
        $maxZoom = (int)option('tearoom1.leaflet-map.tiles.maxZoom', 19);
        $count = 2 ** $z;

        if ($z < 0 || $z > $maxZoom || $x < 0 || $y < 0 || $x >= $count || $y >= $count) {
            return false;
        }

        if ($token === self::PANEL_TOKEN) {
            return kirby()->user() !== null;
        }

        $area = self::decode($token);
        if ($area === null || $z < $area['minZoom'] || $z > $area['maxZoom']) {
            return false;
        }

        $minX = self::tileX($area['west'], $z) - self::MARGIN_TILES;
        $maxX = self::tileX($area['east'], $z) + self::MARGIN_TILES;
        $minY = self::tileY($area['north'], $z) - self::MARGIN_TILES;
        $maxY = self::tileY($area['south'], $z) + self::MARGIN_TILES;

        return $x >= $minX && $x <= $maxX && $y >= $minY && $y <= $maxY;
    }

    /**
     * Route action: fetch the tile, store it in the media folder and return it.
     */
    public static function serve(string $token, int $z, int $x, int $y): Response|false
    {
        if (self::enabled() === false || self::allows($token, $z, $x, $y) === false) {
            return false;
        }

        $file = self::file($token, $z, $x, $y);

        if (F::exists($file) === false) {
            $image = self::fetch($z, $x, $y);
            if ($image === null) {
                return new Response('', 'text/plain', 502);
            }

            F::write($file, $image);
            self::cleanup();
        }

        return new Response(F::read($file), 'image/png', 200, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    protected static function fetch(int $z, int $x, int $y): ?string
    {
        $url = str_replace(
            ['{s}', '{z}', '{x}', '{y}', '{r}'],
            ['a', $z, $x, $y, ''],
            Utils::tiles()['url']
        );

        try {
            $response = Remote::get($url, [
                'timeout' => 10,
                'headers' => [
                    // tile providers require an identifiable user agent
                    'User-Agent' => option(
                        'tearoom1.leaflet-map.tiles.userAgent',
                        'kirby-leaflet-map (+https://github.com/tearoom1/kirby-leaflet-map; ' . kirby()->url() . ')'
                    ),
                    'Referer' => kirby()->url() . '/',
                ],
            ]);
        } catch (\Throwable) {
            return null;
        }

        // header names are lowercase with HTTP/2
        $type = array_change_key_case($response->headers())['content-type'] ?? '';

        return $response->code() === 200 && str_starts_with($type, 'image/')
            ? $response->content()
            : null;
    }

    protected static function file(string $token, int $z, int $x, int $y): string
    {
        return kirby()->root('media') . '/leaflet-map/tiles/' . $token . '/' . $z . '/' . $x . '/' . $y . '.png';
    }

    /**
     * Remove tiles of previous periods, so the cache doesn't grow forever.
     * Runs at most once a day.
     */
    protected static function cleanup(): void
    {
        $root = kirby()->root('media') . '/leaflet-map/tiles';
        $marker = $root . '/.cleanup';

        if (F::exists($marker) && F::modified($marker) > time() - 86400) {
            return;
        }

        touch($marker);
        $current = date('Ym');
        $previous = date('Ym', strtotime('first day of last month'));

        foreach (Dir::read($root) as $token) {
            if ($token === self::PANEL_TOKEN || is_dir($root . '/' . $token) === false) {
                continue;
            }

            $area = self::decode($token);
            if ($area === null || in_array($area['period'], [$current, $previous], true) === false) {
                Dir::remove($root . '/' . $token);
            }
        }

        // the panel cache has no period, renew it after two months
        $panel = $root . '/' . self::PANEL_TOKEN;
        if (is_dir($panel) && filemtime($panel) < strtotime('-2 months')) {
            Dir::remove($panel);
        }
    }

    protected static function baseUrl(): string
    {
        return kirby()->url('media') . '/leaflet-map/tiles';
    }

    protected static function sign(string $payload): string
    {
        return substr(hash_hmac('sha256', $payload, kirby()->contentToken(null, 'leaflet-map-tiles')), 0, 16);
    }

    protected static function tileX(float $lng, int $z): int
    {
        return (int)floor(($lng + 180) / 360 * 2 ** $z);
    }

    protected static function tileY(float $lat, int $z): int
    {
        $lat = max(-85.05112878, min(85.05112878, $lat));
        $rad = deg2rad($lat);

        return (int)floor((1 - log(tan($rad) + 1 / cos($rad)) / M_PI) / 2 * 2 ** $z);
    }
}
