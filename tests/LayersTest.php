<?php

namespace TearoomOne\LeafletMap\Tests;

use Kirby\Cms\Blueprint;
use TearoomOne\LeafletMap\TileProxy;
use TearoomOne\LeafletMap\Utils;

class LayersTest extends TestCase
{
    use MockServer;

    private const POINT = [48.137154, 11.576124];

    public function testTheTilesOptionIsTheOnlyStyleByDefault(): void
    {
        $this->app(['tearoom1.leaflet-map.tiles.url' => 'https://tiles.example.test/{z}/{x}/{y}.png']);

        $this->assertSame(['default'], array_keys(Utils::layers()));
        $this->assertSame('https://tiles.example.test/{z}/{x}/{y}.png', Utils::layer(null)['url']);
    }

    public function testPresetsDefaultAndOwnStyles(): void
    {
        $this->app(['tearoom1.leaflet-map.layers' => [
            'osm',
            'topo',
            'unknown',
            'satellite' => ['label' => 'Satellite', 'url' => 'https://sat.example.test/{z}/{x}/{y}.jpg', 'maxZoom' => 18],
            'default',
        ]]);

        $layers = Utils::layers();

        $this->assertSame(['osm', 'topo', 'satellite', 'default'], array_keys($layers));
        $this->assertSame(17, $layers['topo']['maxZoom']);
        $this->assertSame('Satellite', $layers['satellite']['label']);
        $this->assertSame('osm', Utils::layerKey('osm'));
        $this->assertNull(Utils::layerKey('unknown'));
    }

    public function testMapUsesTheSelectedStyle(): void
    {
        $data = Utils::mapData($this->block(['layer' => 'topo'], ['tearoom1.leaflet-map.layers' => ['osm', 'topo']]));

        $this->assertStringContainsString('opentopomap.org', $data['tiles']['url']);
        $this->assertSame([], $data['layers']);
    }

    public function testVisitorsCanSwitchStylesIfAllowed(): void
    {
        $data = Utils::mapData($this->block(
            ['layer' => 'topo', 'layerSwitch' => 'true'],
            ['tearoom1.leaflet-map.layers' => ['osm', 'topo', 'cyclosm']]
        ));

        // the selected style comes first
        $this->assertSame(['OpenTopoMap', 'OpenStreetMap', 'CyclOSM'], array_column($data['layers'], 'label'));
    }

    public function testUnknownStyleFallsBackToTheFirstOne(): void
    {
        $data = Utils::mapData($this->block(['layer' => 'gone'], ['tearoom1.leaflet-map.layers' => ['topo', 'osm']]));

        $this->assertStringContainsString('opentopomap.org', $data['tiles']['url']);
    }

    public function testStyleFieldsOnlyAppearWithSeveralStyles(): void
    {
        $this->app();
        Blueprint::$loaded = [];
        $fields = Blueprint::load('blocks/leaflet-map')['tabs']['content']['fields'];
        $this->assertArrayNotHasKey('layer', $fields);
    }

    public function testStyleFieldsWithSeveralStyles(): void
    {
        $this->app(['tearoom1.leaflet-map.layers' => ['osm', 'topo']]);
        // blueprints are cached for the request
        Blueprint::$loaded = [];
        $fields = Blueprint::load('blocks/leaflet-map')['tabs']['content']['fields'];

        $this->assertSame(['osm' => 'OpenStreetMap', 'topo' => 'OpenTopoMap'], $fields['layer']['options']);
        $this->assertSame('OpenStreetMap', $fields['layer']['placeholder']);
        $this->assertArrayNotHasKey('required', $fields['layer']);
        $this->assertArrayHasKey('layerSwitch', $fields);
    }

    public function testProxyTokensCarryTheStyle(): void
    {
        $this->app(['tearoom1.leaflet-map.layers' => ['osm', 'topo']]);

        $this->assertNull(TileProxy::decode(TileProxy::token([self::POINT], 10, 17))['layer']);
        $this->assertNull(TileProxy::decode(TileProxy::token([self::POINT], 10, 17, 'osm'))['layer']);
        $this->assertSame('topo', TileProxy::decode(TileProxy::token([self::POINT], 10, 17, 'topo'))['layer']);
    }

    public function testProxyUsesTheZoomLevelsOfTheStyle(): void
    {
        $this->app(['tearoom1.leaflet-map.layers' => ['osm', 'topo']]);
        $token = TileProxy::token([self::POINT], 0, 19, 'topo');

        $this->assertTrue(TileProxy::allows($token, 17, 69749, 45483));
        $this->assertFalse(TileProxy::allows($token, 18, 139498, 90966));
    }

    public function testProxyRejectsStylesThatAreNoLongerConfigured(): void
    {
        $this->app(['tearoom1.leaflet-map.layers' => ['osm', 'topo']]);
        $token = TileProxy::token([self::POINT], 0, 17, 'topo');

        $this->app(['tearoom1.leaflet-map.layers' => ['osm']]);
        $this->assertFalse(TileProxy::allows($token, 14, 8718, 5685));
    }

    public function testProxyFetchesFromTheStyleOfTheToken(): void
    {
        $this->app([
            'tearoom1.leaflet-map.tiles.proxy' => true,
            'tearoom1.leaflet-map.layers'      => [
                'default',
                'other' => ['label' => 'Other', 'url' => self::$mockUrl . '/tiles/{z}/{x}/{y}.png?style=other'],
            ],
            'tearoom1.leaflet-map.tiles.url' => self::$mockUrl . '/tiles/{z}/{x}/{y}.png',
        ]);

        TileProxy::serve(TileProxy::token([self::POINT], 10, 18, 'other'), 14, 8718, 5685);

        $this->assertStringContainsString('style=other', $this->mockRequests()[0]);
    }
}
