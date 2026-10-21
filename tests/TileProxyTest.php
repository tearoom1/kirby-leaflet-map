<?php

namespace TearoomOne\LeafletMap\Tests;

use Kirby\Http\Response;
use TearoomOne\LeafletMap\TileProxy;

class TileProxyTest extends TestCase
{
    use MockServer;

    // Marienplatz, Munich
    private const POINT = [48.137154, 11.576124];

    public function testTokenRoundTrip(): void
    {
        $this->app();
        $token = TileProxy::token([self::POINT, [48.152, 11.592]], 10, 18);

        $this->assertSame([
            'period'  => date('Ym'),
            'south'   => 48.13715,
            'west'    => 11.57612,
            'north'   => 48.152,
            'east'    => 11.592,
            'minZoom' => 10,
            'maxZoom' => 18,
            'layer'   => null,
        ], TileProxy::decode($token));
    }

    public function testTamperedTokenIsRejected(): void
    {
        $this->app();
        $token = TileProxy::token([self::POINT], 10, 18);
        [$payload, $signature] = explode('.', $token);

        // a world-wide area with the original signature
        $forged = rtrim(strtr(base64_encode(date('Ym') . ',-85,-180,85,180,0,19'), '+/', '-_'), '=') . '.' . $signature;

        $this->assertNull(TileProxy::decode($forged));
        $this->assertNull(TileProxy::decode($payload . '.0000000000000000'));
        $this->assertNull(TileProxy::decode('not-a-token'));
        $this->assertFalse(TileProxy::allows($forged, 14, 8718, 5685));
    }

    public function testAllowsOnlyTilesAroundTheMapArea(): void
    {
        $this->app();
        $token = TileProxy::token([self::POINT], 10, 18);

        // tile of Marienplatz at zoom 14, a few tiles next to it, one far away
        $this->assertTrue(TileProxy::allows($token, 14, 8718, 5685));
        $this->assertTrue(TileProxy::allows($token, 14, 8718 + TileProxy::MARGIN_TILES, 5685));
        $this->assertFalse(TileProxy::allows($token, 14, 8718 + TileProxy::MARGIN_TILES + 1, 5685));
        $this->assertFalse(TileProxy::allows($token, 14, 100, 100));
    }

    public function testAllowsOnlyTheZoomLevelsOfTheMap(): void
    {
        $this->app();
        $token = TileProxy::token([self::POINT], 10, 15);

        $this->assertFalse(TileProxy::allows($token, 9, 272, 177));
        $this->assertTrue(TileProxy::allows($token, 10, 544, 355));
        $this->assertFalse(TileProxy::allows($token, 16, 34874, 22741));
    }

    public function testRejectsInvalidTileCoordinates(): void
    {
        $this->app();
        $token = TileProxy::token([self::POINT], 0, 19);

        $this->assertFalse(TileProxy::allows($token, 2, 4, 0));
        $this->assertFalse(TileProxy::allows($token, 2, -1, 0));
        $this->assertFalse(TileProxy::allows($token, 20, 0, 0));
    }

    public function testPanelTilesNeedALoggedInUser(): void
    {
        $app = $this->app([], ['users' => [['email' => 'editor@example.test', 'role' => 'admin']]]);

        $this->assertFalse(TileProxy::allows(TileProxy::PANEL_TOKEN, 3, 4, 2));

        $app->impersonate('editor@example.test');
        $this->assertTrue(TileProxy::allows(TileProxy::PANEL_TOKEN, 3, 4, 2));
    }

    public function testServeIsDisabledByDefault(): void
    {
        $this->app();
        $token = TileProxy::token([self::POINT], 10, 18);

        $this->assertFalse(TileProxy::serve($token, 14, 8718, 5685));
        $this->assertSame([], $this->mockRequests());
    }

    public function testServeFetchesStoresAndReusesTiles(): void
    {
        $this->app($this->proxyOptions());
        $token = TileProxy::token([self::POINT], 10, 18);

        $response = TileProxy::serve($token, 14, 8718, 5685);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->code());
        $this->assertSame('PNG 14/8718/5685', $response->body());
        $this->assertFileExists($this->root . '/media/leaflet-map/tiles/' . $token . '/14/8718/5685.png');

        // the second request is answered from the stored file
        TileProxy::serve($token, 14, 8718, 5685);
        $this->assertCount(1, $this->mockRequests());
        $this->assertStringContainsString('kirby-leaflet-map', $this->mockRequests()[0]);
    }

    public function testServeRejectsTilesOutsideTheArea(): void
    {
        $this->app($this->proxyOptions());
        $token = TileProxy::token([self::POINT], 10, 18);

        $this->assertFalse(TileProxy::serve($token, 14, 100, 100));
        $this->assertSame([], $this->mockRequests());
    }

    public function testServeReportsUpstreamErrors(): void
    {
        $this->app($this->proxyOptions(['tearoom1.leaflet-map.tiles.url' => self::$mockUrl . '/tiles-html/{z}/{x}/{y}.png']));
        $token = TileProxy::token([[85, -180]], 0, 19);

        $response = TileProxy::serve($token, 1, 1, 1);

        $this->assertSame(502, $response->code());
        $this->assertFileDoesNotExist($this->root . '/media/leaflet-map/tiles/' . $token . '/1/1/1.png');
    }

    public function testMapDataUsesTheProxyUrl(): void
    {
        $block = $this->block([
            'mapItems' => [['title' => 'A', 'location' => ['lat' => self::POINT[0], 'lng' => self::POINT[1]]]],
        ], $this->proxyOptions());

        $url = \TearoomOne\LeafletMap\Utils::mapData($block)['tiles']['url'];

        $this->assertStringStartsWith('https://example.test/media/leaflet-map/tiles/', $url);
        $this->assertStringEndsWith('/{z}/{x}/{y}.png', $url);
    }

    private function proxyOptions(array $options = []): array
    {
        return array_merge([
            'tearoom1.leaflet-map.tiles.proxy' => true,
            'tearoom1.leaflet-map.tiles.url'   => self::$mockUrl . '/tiles/{z}/{x}/{y}.png',
        ], $options);
    }

    public function testCleanupRemovesTilesOfOldPeriods(): void
    {
        $this->app();
        $root = $this->root . '/media/leaflet-map/tiles';
        $current = TileProxy::token([self::POINT], 10, 18);
        $previous = $this->tokenForPeriod(date('Ym', strtotime('first day of last month')));
        $old = $this->tokenForPeriod('202001');
        foreach ([$current, $previous, $old, 'not-a-token', TileProxy::PANEL_TOKEN] as $token) {
            $this->tile($root . '/' . $token);
        }

        $this->cleanup();

        $this->assertDirectoryExists($root . '/' . $current);
        $this->assertDirectoryExists($root . '/' . $previous);
        $this->assertDirectoryExists($root . '/' . TileProxy::PANEL_TOKEN);
        $this->assertDirectoryDoesNotExist($root . '/' . $old);
        $this->assertDirectoryDoesNotExist($root . '/not-a-token');
    }

    public function testCleanupRenewsThePanelCacheAfterTwoMonths(): void
    {
        $this->app();
        $panel = $this->root . '/media/leaflet-map/tiles/' . TileProxy::PANEL_TOKEN;
        $this->tile($panel);
        touch($panel, strtotime('-3 months'));

        $this->cleanup();

        $this->assertDirectoryDoesNotExist($panel);
    }

    public function testCleanupRunsAtMostOncePerDay(): void
    {
        $this->app();
        $root = $this->root . '/media/leaflet-map/tiles';
        $old = $this->tokenForPeriod('202001');
        $this->tile($root . '/' . $old);
        touch($root . '/.cleanup');

        $this->cleanup();

        $this->assertDirectoryExists($root . '/' . $old);
    }

    /**
     * A validly signed token for a map created in the given month (Ym)
     */
    private function tokenForPeriod(string $period): string
    {
        $payload = $period . ',48.13715,11.57612,48.152,11.592,10,18';
        $sign = new \ReflectionMethod(TileProxy::class, 'sign');

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . $sign->invoke(null, $payload);
    }

    private function tile(string $tokenDir): void
    {
        mkdir($tokenDir . '/10/550', 0777, true);
        file_put_contents($tokenDir . '/10/550/335.png', 'png');
    }

    private function cleanup(): void
    {
        (new \ReflectionMethod(TileProxy::class, 'cleanup'))->invoke(null);
    }
}
