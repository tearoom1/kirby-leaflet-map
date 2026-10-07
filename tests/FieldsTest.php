<?php

namespace TearoomOne\LeafletMap\Tests;

use Kirby\Cms\Page;
use Kirby\Form\Field;

class FieldsTest extends TestCase
{
    public function testLocationFieldNormalizesTheValue(): void
    {
        $this->assertSame(['lat' => 48.1, 'lng' => 11.5], $this->field('leaflet-location', "lat: '48.1'\nlng: '11.5'")->value());
        $this->assertSame(['lat' => 48.1, 'lng' => 11.5], $this->field('leaflet-location', ['lat' => 48.1, 'lng' => 11.5])->value());
        $this->assertNull($this->field('leaflet-location', ['lat' => 'x', 'lng' => 11.5])->value());
        $this->assertNull($this->field('leaflet-location', null)->value());
    }

    public function testLocationFieldSavesOnlyCompleteLocations(): void
    {
        $this->assertSame(['lat' => 48.1, 'lng' => 11.5], $this->field('leaflet-location', ['lat' => 48.1, 'lng' => 11.5])->data());
        $this->assertNull($this->field('leaflet-location', null)->data());
    }

    public function testPathFieldKeepsValidPointsInOrder(): void
    {
        $field = $this->field('leaflet-path', [
            ['lat' => 48.1, 'lng' => 11.5],
            ['lat' => 'x', 'lng' => 11.5],
            ['lat' => '48.2', 'lng' => '11.6'],
        ]);

        $this->assertSame([['lat' => 48.1, 'lng' => 11.5], ['lat' => 48.2, 'lng' => 11.6]], $field->value());
        $this->assertSame([['lat' => 48.1, 'lng' => 11.5], ['lat' => 48.2, 'lng' => 11.6]], $field->data());
        $this->assertNull($this->field('leaflet-path', [])->data());
    }

    public function testFieldsUseTheTileOptionsAndPanelDefaults(): void
    {
        $field = $this->field('leaflet-location', null, [
            'tearoom1.leaflet-map.tiles.url' => 'https://tiles.example.test/{z}/{x}/{y}.png',
            'tearoom1.leaflet-map.panel.center' => [48, 11],
        ]);

        $this->assertSame('https://tiles.example.test/{z}/{x}/{y}.png', $field->tiles()['url']);
        $this->assertSame([48, 11], $field->center());
        $this->assertTrue($field->search());
    }

    public function testFieldsUseThePanelTokenWithTheProxy(): void
    {
        $field = $this->field('leaflet-path', null, ['tearoom1.leaflet-map.tiles.proxy' => true]);

        $this->assertSame('https://example.test/media/leaflet-map/tiles/panel/{z}/{x}/{y}.png', $field->tiles()['url']);
    }

    private function field(string $type, mixed $value, array $options = []): Field
    {
        $this->app($options);

        return new Field($type, [
            'model' => new Page(['slug' => 'test']),
            'value' => $value,
        ]);
    }
}
