<?php

namespace TearoomOne\LeafletMap\Tests;

use TearoomOne\LeafletMap\Icons;
use TearoomOne\LeafletMap\Utils;

class IconsTest extends TestCase
{
    private const SVG = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg>';

    public function testBuiltInSymbolsHaveLabelsAndMarkup(): void
    {
        $this->app();

        $this->assertTrue(Icons::exists('stop'));
        $this->assertFalse(Icons::exists('unknown'));
        $this->assertFalse(Icons::exists(null));
        $this->assertSame('Haltestelle', Icons::label('stop', 'de'));
        $this->assertSame('Stop', Icons::label('stop', 'fr'));
        $this->assertStringStartsWith('<svg viewBox="0 0 24 24"', Icons::svg('bus'));
    }

    public function testSitesCanAddAndRemoveSymbols(): void
    {
        $this->app(['tearoom1.leaflet-map.icons' => [
            'ferry' => ['label' => ['en' => 'Ferry', 'de' => 'Fähre'], 'svg' => self::SVG],
            'logo'  => ['svg' => self::SVG],
            'bus'   => false,
            'broken' => ['label' => 'No markup'],
        ]]);

        $this->assertSame(self::SVG, Icons::svg('ferry'));
        $this->assertSame('Fähre', Icons::label('ferry', 'de'));
        $this->assertSame('logo', Icons::label('logo', 'de'));
        $this->assertFalse(Icons::exists('bus'));
        $this->assertFalse(Icons::exists('broken'));
    }

    public function testMapDataContainsEachUsedSymbolOnce(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'A', 'location' => ['lat' => 1, 'lng' => 1], 'icon' => 'bus', 'color' => '#f1c40f'],
                ['title' => 'B', 'location' => ['lat' => 2, 'lng' => 2], 'icon' => 'bus'],
                ['title' => 'C', 'location' => ['lat' => 3, 'lng' => 3], 'icon' => 'unknown'],
            ],
        ]));

        $this->assertSame(['bus'], array_keys($data['icons']));
        $this->assertSame('bus', $data['locations'][0]['icon']);
        $this->assertNull($data['locations'][2]['icon']);
        // dark symbol on a yellow marker
        $this->assertSame('#1d1d1d', $data['locations'][0]['iconColor']);
        $this->assertSame('#ffffff', $data['locations'][1]['iconColor']);
    }

    public function testLegendGroupsLocationsAndPaths(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'A', 'location' => ['lat' => 1, 'lng' => 1], 'icon' => 'bus', 'color' => '#ff0000'],
                ['title' => 'B', 'location' => ['lat' => 2, 'lng' => 2], 'icon' => 'bus', 'color' => '#00ff00'],
                ['title' => 'C', 'location' => ['lat' => 3, 'lng' => 3], 'category' => 'Our shops', 'color' => '#0000ff'],
                ['title' => 'D', 'location' => ['lat' => 4, 'lng' => 4], 'icon' => 'shop', 'category' => 'Our shops'],
                ['title' => 'E', 'location' => ['lat' => 5, 'lng' => 5]],
                ['title' => 'F', 'location' => ['lat' => 6, 'lng' => 6]],
            ],
            'paths' => [
                ['title' => 'Walk', 'color' => '#2980b9', 'line' => [['lat' => 1, 'lng' => 1], ['lat' => 2, 'lng' => 2]]],
                ['title' => 'Other walk', 'line' => [['lat' => 1, 'lng' => 1], ['lat' => 2, 'lng' => 2]]],
                ['title' => 'Tour', 'category' => 'Bike route', 'line' => [['lat' => 1, 'lng' => 1], ['lat' => 2, 'lng' => 2]]],
            ],
        ]));

        $legend = Utils::legend($data);

        // plain markers and paths without an entry share one general line each
        $this->assertSame(['Bus', 'Our shops', 'Location', 'Path', 'Bike route'], array_column($legend, 'label'));
        // the first location of a group gives the symbol and color
        $this->assertSame('#ff0000', $legend[0]['color']);
        $this->assertNotNull($legend[0]['icon']);
        $this->assertNull($legend[1]['icon']);
        $this->assertSame('#2980b9', $legend[3]['color']);
        $this->assertTrue($legend[3]['path']);
    }

    public function testSnippetRendersTheLegendOnlyIfEnabled(): void
    {
        $content = [
            'mapItems' => [['title' => 'A', 'location' => ['lat' => 1, 'lng' => 1], 'icon' => 'bus', 'category' => '<b>Stops</b>']],
        ];

        $this->assertStringNotContainsString('leaflet-map__legend', (string)$this->block($content));

        $html = (string)$this->block($content + ['legend' => 'true']);
        $this->assertStringContainsString('class="leaflet-map__legend"', $html);
        $this->assertStringContainsString('&lt;b&gt;Stops&lt;/b&gt;', $html);
    }

    public function testContrast(): void
    {
        $this->assertSame('#ffffff', Utils::contrast('#3388ff'));
        $this->assertSame('#1d1d1d', Utils::contrast('#fff'));
        $this->assertSame('#1d1d1d', Utils::contrast('#f1c40fcc'));
    }
}
