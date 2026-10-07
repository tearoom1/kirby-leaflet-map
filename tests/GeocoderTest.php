<?php

namespace TearoomOne\LeafletMap\Tests;

use TearoomOne\LeafletMap\Geocoder;

class GeocoderTest extends TestCase
{
    use MockServer;

    public function testReturnsDistinctResultsWithCoordinates(): void
    {
        $this->app($this->options());

        $this->assertSame([
            ['label' => 'Marienplatz, München', 'lat' => 48.137154, 'lng' => 11.576124],
            ['label' => 'Marienplatz, Bielefeld', 'lat' => 52.0, 'lng' => 8.5],
        ], Geocoder::search('Marienplatz', 'de'));
    }

    public function testSendsQueryLanguageAndUserAgent(): void
    {
        $this->app($this->options());
        Geocoder::search('Marienplatz', 'de');

        $request = $this->mockRequests()[0];
        $this->assertStringContainsString('q=Marienplatz', $request);
        $this->assertStringContainsString('accept-language=de', $request);
        $this->assertStringContainsString('kirby-leaflet-map', $request);
    }

    public function testCachesResults(): void
    {
        $this->app($this->options());

        Geocoder::search('Marienplatz', 'de');
        Geocoder::search('Marienplatz', 'de');

        $this->assertCount(1, $this->mockRequests());
    }

    public function testIgnoresShortQueries(): void
    {
        $this->app($this->options());

        $this->assertSame([], Geocoder::search(' ab ', 'de'));
        $this->assertSame([], $this->mockRequests());
    }

    public function testCanBeDisabled(): void
    {
        $this->app($this->options(['tearoom1.leaflet-map.geocoder' => false]));

        $this->assertFalse(Geocoder::enabled());
        $this->assertSame([], Geocoder::search('Marienplatz', 'de'));
        $this->assertSame([], $this->mockRequests());
    }

    public function testUnreachableServiceReturnsNoResults(): void
    {
        $this->app($this->options(['tearoom1.leaflet-map.geocoder.url' => 'http://127.0.0.1:1/search']));

        $this->assertSame([], Geocoder::search('Marienplatz', 'de'));
    }

    private function options(array $options = []): array
    {
        return array_merge([
            'tearoom1.leaflet-map.geocoder.url' => self::$mockUrl . '/search',
        ], $options);
    }
}
