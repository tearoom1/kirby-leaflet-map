<?php
/**
 * Legend below the map: symbols, colors and paths with their meaning.
 *
 * @var array $entries see TearoomOne\LeafletMap\Utils::legend(), colors are validated there
 */
if (empty($entries)) {
    return;
}
?>
<ul class="leaflet-map__legend" aria-label="<?= esc(t('tearoom1.leaflet-map.legend')) ?>">
    <?php foreach ($entries as $entry): ?>
        <li class="leaflet-map__legend-entry">
            <?php if ($entry['path']): ?>
                <span class="leaflet-map__legend-line" style="--marker: <?= $entry['color'] ?>"></span>
            <?php elseif ($entry['icon'] !== null): ?>
                <span class="leaflet-map__symbol" style="--marker: <?= $entry['color'] ?>; --symbol: <?= $entry['iconColor'] ?>"><?= $entry['icon'] ?></span>
            <?php else: ?>
                <span class="leaflet-map__legend-dot" style="--marker: <?= $entry['color'] ?>"></span>
            <?php endif ?>
            <span><?= esc($entry['label']) ?></span>
        </li>
    <?php endforeach ?>
</ul>
