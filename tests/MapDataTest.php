<?php

namespace TearoomOne\LeafletMap\Tests;

use TearoomOne\LeafletMap\Utils;

class MapDataTest extends TestCase
{
    public function testReadsLocationsOfThePickerField(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'Marienplatz', 'location' => ['lat' => 48.137154, 'lng' => 11.576124], 'size' => '3', 'color' => '#e74c3c', 'tooltip' => 'true', 'center' => 'false'],
            ],
        ]));

        $this->assertSame([
            'lat'         => 48.137154,
            'lng'         => 11.576124,
            'title'       => 'Marienplatz',
            'description' => '',
            'size'        => 3,
            'color'       => '#e74c3c',
            'tooltip'     => true,
            'center'      => false,
        ], $data['locations'][0]);
    }

    public function testReadsFormerLatLngFields(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'Old', 'lat' => '48.1', 'lng' => '11.5'],
            ],
        ]));

        $this->assertSame(48.1, $data['locations'][0]['lat']);
        $this->assertSame(11.5, $data['locations'][0]['lng']);
    }

    public function testPickerFieldWinsOverFormerFields(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'Moved', 'location' => ['lat' => 52.5, 'lng' => 13.4], 'lat' => '48.1', 'lng' => '11.5'],
            ],
        ]));

        $this->assertSame([52.5, 13.4], [$data['locations'][0]['lat'], $data['locations'][0]['lng']]);
    }

    public function testSkipsHiddenAndInvalidLocations(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'Hidden', 'location' => ['lat' => 48.1, 'lng' => 11.5], 'hide' => 'true'],
                ['title' => 'No coordinates'],
                ['title' => 'Not numeric', 'lat' => 'abc', 'lng' => '11.5'],
                ['title' => 'Out of range', 'location' => ['lat' => 120, 'lng' => 11.5]],
                ['title' => 'Valid', 'location' => ['lat' => 48.1, 'lng' => 11.5]],
            ],
        ]));

        $this->assertSame(['Valid'], array_column($data['locations'], 'title'));
    }

    public function testSanitizesSizeAndColor(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'A', 'location' => ['lat' => 1, 'lng' => 1], 'size' => '9', 'color' => 'red;background:url(x)'],
                ['title' => 'B', 'location' => ['lat' => 1, 'lng' => 1]],
            ],
        ]));

        $this->assertSame(5, $data['locations'][0]['size']);
        $this->assertSame('#3388ff', $data['locations'][0]['color']);
        $this->assertSame(1, $data['locations'][1]['size']);
    }

    public function testRendersDescriptionWithKirbytext(): void
    {
        $data = Utils::mapData($this->block([
            'mapItems' => [
                ['title' => 'A', 'location' => ['lat' => 1, 'lng' => 1], 'description' => 'With **bold** text'],
            ],
        ]));

        $this->assertSame('With <strong>bold</strong> text', $data['locations'][0]['description']);
    }

    public function testReadsPathsOfThePathEditor(): void
    {
        $data = Utils::mapData($this->block([
            'paths' => [
                ['title' => 'Walk', 'color' => '#2c3e50', 'tooltip' => 'false', 'line' => [
                    ['lat' => 48.1, 'lng' => 11.5],
                    ['lat' => 48.2, 'lng' => 11.6],
                ]],
            ],
        ]));

        $this->assertSame([
            'title'   => 'Walk',
            'color'   => '#2c3e50',
            'tooltip' => false,
            'points'  => [[48.1, 11.5], [48.2, 11.6]],
        ], $data['paths'][0]);
    }

    public function testReadsFormerPathPoints(): void
    {
        $data = Utils::mapData($this->block([
            'paths' => [
                ['title' => 'Old', 'points' => [
                    ['lat' => '48.1', 'lng' => '11.5'],
                    ['location' => ['lat' => 48.2, 'lng' => 11.6]],
                ]],
            ],
        ]));

        $this->assertSame([[48.1, 11.5], [48.2, 11.6]], $data['paths'][0]['points']);
    }

    public function testSkipsPathsWithLessThanTwoPoints(): void
    {
        $data = Utils::mapData($this->block([
            'paths' => [
                ['title' => 'Single', 'line' => [['lat' => 48.1, 'lng' => 11.5]]],
            ],
        ]));

        $this->assertSame([], $data['paths']);
    }

    public function testZoomLevelsAreOrdered(): void
    {
        $data = Utils::mapData($this->block(['defaultZoom' => '12', 'minZoom' => '16', 'maxZoom' => '8']));

        $this->assertSame(['default' => 12, 'min' => 8, 'max' => 16], $data['zoom']);
    }

    public function testLabelsDefaultToPerLocation(): void
    {
        $this->assertSame('individual', Utils::mapData($this->block([]))['labels']);
        $this->assertSame('hover', Utils::mapData($this->block(['labels' => 'hover']))['labels']);
        $this->assertSame('individual', Utils::mapData($this->block(['labels' => 'unknown']))['labels']);
    }

    public function testTilesComeFromTheOptions(): void
    {
        $data = Utils::mapData($this->block([], [
            'tearoom1.leaflet-map.tiles.url'         => 'https://tiles.example.test/{z}/{x}/{y}.png',
            'tearoom1.leaflet-map.tiles.attribution' => 'Example',
        ]));

        $this->assertSame('https://tiles.example.test/{z}/{x}/{y}.png', $data['tiles']['url']);
        $this->assertSame('Example', $data['tiles']['attribution']);
    }

    public function testJsonCannotBreakOutOfTheScriptTag(): void
    {
        $json = Utils::json(['title' => '</script><script>alert(1)</script>', 'quote' => "\"'&"]);

        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('>', $json);
        $this->assertStringNotContainsString("'", $json);
        $this->assertSame('</script><script>alert(1)</script>', json_decode($json, true)['title']);
    }

    public function testSnippetEscapesTheTitle(): void
    {
        $block = $this->block([
            'mapTitle' => '<img src=x onerror=alert(1)>',
            'mapItems' => [['title' => '</script>', 'location' => ['lat' => 1, 'lng' => 1]]],
        ]);

        $html = (string)$block;

        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertSame(1, substr_count($html, '</script>'));
    }
}
