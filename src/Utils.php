<?php

namespace TearoomOne\LeafletMap;

class Utils
{
    static function printAsset($page, $assetType): void
    {
        if (!option('tearoom1.leaflet-map.enabled', true)) {
            return;
        }
        if (option('tearoom1.leaflet-map.alwaysIncludeAssets', true)) {
            self::printAssetString($assetType);
            return;
        }
        // otherwise check if there is a uniform contact block present on the page
        $fieldsToCheck = array_keys(array_filter($page->blueprint()->fields(),
            fn($item) => in_array($item['type'], ['blocks', 'layout'])));
        foreach ($fieldsToCheck as $fieldName) {
            if ($page->{$fieldName}()->toBlocks()->hasType('leaflet-map')) {
                self::printAssetString($assetType);
                break;
            }
        }
    }

    /**
     * @param $assetType
     * @return void
     */
    private static function printAssetString($assetType): void
    {
        if ($assetType === 'css') {
            echo css(['media/plugins/tearoom1/kirby-leaflet-map/css/leaflet-map.css']);
        } elseif ($assetType === 'js') {
            echo js(['media/plugins/tearoom1/kirby-leaflet-map/js/leaflet.js']);
            echo js(['media/plugins/tearoom1/kirby-leaflet-map/js/leaflet-map.js']);
        }
    }
}
