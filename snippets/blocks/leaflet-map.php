<?php
/** @var \Kirby\Cms\Block $block
 * @var \Kirby\Cms\Page $page
 */
$isPopup = $block->displayMode()->value() === 'popup';
?>
<div class="leaflet-map" id="leaflet-map-<?= $block->id() ?>">
    <?php if ($block->mapTitle()->isNotEmpty()): ?>
        <h2 class="leaflet-map__title"><?= $block->mapTitle() ?></h2>
    <?php endif; ?>

    <?php if ($block->mapDescription()->isNotEmpty()): ?>
        <div class="leaflet-map__description">
            <?= $block->mapDescription()->kt() ?>
        </div>
    <?php endif; ?>

    <div class="leaflet-map__container">
        <?php if ($isPopup): ?>
            <?php if ($thumbnail = $block->thumbnail()->toFile()): ?>
                <div class="leaflet-map__thumbnail">
                    <img src="<?= $thumbnail->thumb(['width' => 800] + option('thumbs.presets.no_crop', []))->url() ?>"
                         alt="<?= $block->mapTitle()->or('Map of Arillas, Corfu') ?>"
                         loading="lazy"
                         class="leaflet-map__thumbnail-img">
                    <button class="leaflet-map__open-btn">View Interactive Map</button>
                </div>
            <?php endif ?>

            <div class="leaflet-map__interactive" aria-hidden="true">
                <div class="leaflet-map__fullscreen">
                    <div class="leaflet-map__header">
                        <h3><?= $block->mapTitle()->or('Arillas Map') ?></h3>
                        <button class="leaflet-map__close-btn" aria-label="Close Map">×</button>
                    </div>
                    <div class="leaflet-map__map" data-block-id="<?= $block->id() ?>"></div>
                </div>
            </div>
        <?php else: ?>
            <div class="leaflet-map__map" data-block-id="<?= $block->id() ?>"></div>
        <?php endif ?>
    </div>

    <script type="application/json" class="leaflet-map__data">
        {
          "displayMode": "<?= $block->displayMode() ?>",
          "locations": <?= json_encode($block->mapItems()->toStructure()->toArray()) ?>,
          "paths": <?= json_encode($block->paths()->toStructure()->toArray()) ?>,
          "zoomSettings": {
            "defaultZoom": <?= $block->defaultZoom()->or(15) ?>,
            "minZoom": <?= $block->minZoom()->or(10) ?>,
            "maxZoom": <?= $block->maxZoom()->or(19) ?>
          }
        }
    </script>
</div>
