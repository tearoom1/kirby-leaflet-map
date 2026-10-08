<?php

namespace TearoomOne\LeafletMap;

use Kirby\Filesystem\F;
use Kirby\Http\Remote;

/**
 * Address search for the panel picker, proxied through the server so the
 * geocoding service sees the server, not the editor. Follows the Nominatim
 * usage policy: identifiable user agent, at most one request per second,
 * results are cached.
 */
class Geocoder
{
    public const DEFAULT_URL = 'https://nominatim.openstreetmap.org/search';

    public static function enabled(): bool
    {
        return option('tearoom1.leaflet-map.geocoder', true) !== false;
    }

    /**
     * @return array<int, array{label: string, lat: float, lng: float}>
     */
    public static function search(string $query, ?string $language = null): array
    {
        $query = trim($query);
        if (self::enabled() === false || mb_strlen($query) < 3) {
            return [];
        }

        $cache = kirby()->cache('tearoom1.leaflet-map.geocode');
        $key = md5($query . '|' . $language);

        if (($cached = $cache->get($key)) !== null) {
            return $cached;
        }

        self::throttle();

        $url = option('tearoom1.leaflet-map.geocoder.url', self::DEFAULT_URL) . '?' . http_build_query([
            'q'               => $query,
            'format'          => 'jsonv2',
            'limit'           => 5,
            'accept-language' => $language ?? 'en',
        ]);

        try {
            $response = Remote::get($url, [
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => option(
                        'tearoom1.leaflet-map.tiles.userAgent',
                        'kirby-leaflet-map (+https://github.com/tearoom1/kirby-leaflet-map; ' . kirby()->url() . ')'
                    ),
                    'Referer' => kirby()->url() . '/',
                ],
            ]);
        } catch (\Throwable) {
            return [];
        }

        if ($response->code() !== 200) {
            return [];
        }

        $results = [];
        foreach ($response->json() ?? [] as $place) {
            $label = (string)($place['display_name'] ?? '');

            // the service may return the same place more than once
            if (!is_numeric($place['lat'] ?? null) || !is_numeric($place['lon'] ?? null) || isset($results[$label])) {
                continue;
            }

            $results[$label] = [
                'label' => $label,
                'lat'   => round((float)$place['lat'], 6),
                'lng'   => round((float)$place['lon'], 6),
            ];
        }

        $results = array_values($results);

        // one week, addresses rarely move
        $cache->set($key, $results, 60 * 24 * 7);

        return $results;
    }

    /**
     * Wait so requests to the geocoding service are at least a second apart.
     */
    protected static function throttle(): void
    {
        $marker = kirby()->root('cache') . '/leaflet-map-geocode.last';
        $last = F::exists($marker) ? (float)F::read($marker) : 0.0;
        $wait = 1.0 - (microtime(true) - $last);

        if ($wait > 0) {
            usleep((int)($wait * 1_000_000));
        }

        F::write($marker, (string)microtime(true));
    }
}
