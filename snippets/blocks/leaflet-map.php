<?php
/** @var \Kirby\Cms\Block $block
 * @var \Kirby\Cms\Page $page
 */

use TearoomOne\LeafletMap\Utils;

$isPopup = $block->displayMode()->value() === 'popup';
// Only contact the tile server after a click (privacy / GDPR)
$loadOnClick = !$isPopup && option('tearoom1.leaflet-map.loadOnClick', false);
$title = $block->mapTitle()->value() ?? '';
$mapLabel = $title !== '' ? $title : t('tearoom1.leaflet-map.map');
$mapData = Utils::mapData($block);
$legend = $block->legend()->toBool() ? Utils::legend($mapData) : [];
?>
<div class="leaflet-map" id="leaflet-map-<?= $block->id() ?>">
    <?php if ($title !== ''): ?>
        <h2 class="leaflet-map__title"><?= esc($title) ?></h2>
    <?php endif; ?>

    <?php if ($block->mapDescription()->isNotEmpty()): ?>
        <div class="leaflet-map__description">
            <?= $block->mapDescription()->kt() ?>
        </div>
    <?php endif; ?>

    <div class="leaflet-map__container">
        <?php if ($isPopup): ?>
            <div class="leaflet-map__thumbnail">
                <?php if ($thumbnail = $block->thumbnail()->toFile()): ?>
                    <img src="<?= $thumbnail->thumb(['width' => 800] + option('thumbs.presets.no_crop', []))->url() ?>"
                         alt="<?= esc($mapLabel) ?>"
                         loading="lazy"
                         class="leaflet-map__thumbnail-img">
                <?php endif ?>
                <button type="button" class="leaflet-map__open-btn"><?= esc(t('tearoom1.leaflet-map.open')) ?></button>
            </div>

            <div class="leaflet-map__interactive" aria-hidden="true">
                <div class="leaflet-map__fullscreen" role="dialog" aria-label="<?= esc($mapLabel) ?>">
                    <div class="leaflet-map__header">
                        <h3><?= esc($mapLabel) ?></h3>
                        <button type="button" class="leaflet-map__close-btn" aria-label="<?= esc(t('tearoom1.leaflet-map.close')) ?>">×</button>
                    </div>
                    <div class="leaflet-map__map" data-block-id="<?= $block->id() ?>"></div>
                    <?php snippet('leaflet-map/legend', ['entries' => $legend]) ?>
                </div>
            </div>
        <?php else: ?>
            <div class="leaflet-map__map" data-block-id="<?= $block->id() ?>" aria-label="<?= esc($mapLabel) ?>">
                <?php if ($loadOnClick): ?>
                    <div class="leaflet-map__consent">
                        <p><?= t('tearoom1.leaflet-map.consent') ?></p>
                        <button type="button" class="leaflet-map__load-btn"><?= esc(t('tearoom1.leaflet-map.load')) ?></button>
                    </div>
                <?php endif ?>
            </div>
            <?php snippet('leaflet-map/legend', ['entries' => $legend]) ?>
        <?php endif ?>
    </div>

    <script type="application/json" class="leaflet-map__data"><?= Utils::json($mapData) ?></script>
</div>
