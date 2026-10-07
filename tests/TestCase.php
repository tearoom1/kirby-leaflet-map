<?php

namespace TearoomOne\LeafletMap\Tests;

use Kirby\Cms\App;
use Kirby\Cms\Block;
use Kirby\Filesystem\Dir;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/leaflet-map-tests/' . uniqid();
        Dir::make($this->root . '/content');
    }

    protected function tearDown(): void
    {
        // no App::destroy(): it would also drop the plugin, which is only registered once
        Dir::remove($this->root);
    }

    protected function app(array $options = [], array $props = []): App
    {
        return new App(array_replace_recursive([
            'roots' => [
                'index'   => $this->root,
                'content' => $this->root . '/content',
                'media'   => $this->root . '/media',
                'cache'   => $this->root . '/cache',
            ],
            'urls' => [
                'index' => 'https://example.test',
            ],
            'options' => $options,
        ], $props));
    }

    /**
     * A leaflet-map block with the given content on a test page.
     */
    protected function block(array $content, array $options = []): Block
    {
        $this->app($options, [
            'site' => [
                'children' => [
                    [
                        'slug' => 'map',
                        'content' => [
                            'title' => 'Map',
                            'blocks' => json_encode([
                                ['id' => 'block-1', 'type' => 'leaflet-map', 'content' => $content],
                            ]),
                        ],
                    ],
                ],
            ],
        ]);

        return page('map')->blocks()->toBlocks()->first();
    }
}
