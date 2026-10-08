<?php

namespace TearoomOne\LeafletMap;

/**
 * Marker symbols. Most of them are icons of Lucide (https://lucide.dev, ISC
 * license, see LICENSE-lucide), drawn as white lines on the colored marker.
 * Sites can add their own symbols or remove built-in ones with the `icons`
 * option.
 */
class Icons
{
    public const BUILT_IN = [
        'stop' => ['en' => 'Stop', 'de' => 'Haltestelle', 'svg' => '<path d="M8 6v12"/><path d="M16 6v12"/><path d="M8 12h8"/>'],
        'bus' => ['en' => 'Bus', 'de' => 'Bus', 'svg' => '<path d="M8 6v6"/><path d="M15 6v6"/><path d="M2 12h19.6"/><path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-.4-.1-.8-.2-1.2l-1.4-5C20.1 6.8 19.1 6 18 6H4a2 2 0 0 0-2 2v10h3"/><circle cx="7" cy="18" r="2"/><path d="M9 18h5"/><circle cx="16" cy="18" r="2"/>'],
        'train' => ['en' => 'Train', 'de' => 'Bahn', 'svg' => '<path d="M8 3.1V7a4 4 0 0 0 8 0V3.1"/><path d="m9 15-1-1"/><path d="m15 15 1-1"/><path d="M9 19c-2.8 0-5-2.2-5-5v-4a8 8 0 0 1 16 0v4c0 2.8-2.2 5-5 5Z"/><path d="m8 19-2 3"/><path d="m16 19 2 3"/>'],
        'tram' => ['en' => 'Tram', 'de' => 'Tram', 'svg' => '<rect width="16" height="16" x="4" y="3" rx="2"/><path d="M4 11h16"/><path d="M12 3v8"/><path d="m8 19-2 3"/><path d="m18 22-2-3"/><path d="M8 15h.01"/><path d="M16 15h.01"/>'],
        'bike' => ['en' => 'Bicycle', 'de' => 'Fahrrad', 'svg' => '<circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/>'],
        'car' => ['en' => 'Car', 'de' => 'Auto', 'svg' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>'],
        'parking' => ['en' => 'Parking', 'de' => 'Parkplatz', 'svg' => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>'],
        'charging' => ['en' => 'Charging station', 'de' => 'Ladestation', 'svg' => '<path d="M6.3 20.3a2.4 2.4 0 0 0 3.4 0L12 18l-6-6-2.3 2.3a2.4 2.4 0 0 0 0 3.4Z"/><path d="m2 22 3-3"/><path d="M7.5 13.5 10 11"/><path d="M10.5 16.5 13 14"/><path d="m18 3-4 4h6l-4 4"/>'],
        'harbour' => ['en' => 'Harbour', 'de' => 'Hafen', 'svg' => '<path d="M12 22V8"/><path d="M5 12H2a10 10 0 0 0 20 0h-3"/><circle cx="12" cy="5" r="3"/>'],
        'restaurant' => ['en' => 'Restaurant', 'de' => 'Restaurant', 'svg' => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>'],
        'cafe' => ['en' => 'Café', 'de' => 'Café', 'svg' => '<path d="M10 2v2"/><path d="M14 2v2"/><path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 1 1 0 8h-1"/><path d="M6 2v2"/>'],
        'bar' => ['en' => 'Bar', 'de' => 'Bar', 'svg' => '<path d="M17 11h1a3 3 0 0 1 0 6h-1"/><path d="M9 12v6"/><path d="M13 12v6"/><path d="M14 7.5c-1 0-1.44.5-3 .5s-2-.5-3-.5-1.72.5-2.5.5a2.5 2.5 0 0 1 0-5c.78 0 1.57.5 2.5.5S9.44 2 11 2s2 1.5 3 1.5 1.72-.5 2.5-.5a2.5 2.5 0 0 1 0 5c-.78 0-1.5-.5-2.5-.5Z"/><path d="M5 8v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8"/>'],
        'hotel' => ['en' => 'Accommodation', 'de' => 'Unterkunft', 'svg' => '<path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/>'],
        'camping' => ['en' => 'Camping', 'de' => 'Camping', 'svg' => '<path d="M3.5 21 14 3"/><path d="M20.5 21 10 3"/><path d="M15.5 21 12 15l-3.5 6"/><path d="M2 21h20"/>'],
        'shop' => ['en' => 'Shop', 'de' => 'Einkaufen', 'svg' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>'],
        'info' => ['en' => 'Information', 'de' => 'Information', 'svg' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>'],
        'entrance' => ['en' => 'Entrance', 'de' => 'Eingang', 'svg' => '<path d="M13 4h3a2 2 0 0 1 2 2v14"/><path d="M2 20h3"/><path d="M13 20h9"/><path d="M10 12v.01"/><path d="M13 4.562v16.157a1 1 0 0 1-1.242.97L5 20V5.562a2 2 0 0 1 1.515-1.94l4-1A2 2 0 0 1 13 4.561Z"/>'],
        'toilet' => ['en' => 'Toilet', 'de' => 'WC', 'svg' => '<path d="M7 12h13a1 1 0 0 1 1 1 5 5 0 0 1-5 5h-.598a.5.5 0 0 0-.424.765l1.544 2.47a.5.5 0 0 1-.424.765H5.402a.5.5 0 0 1-.424-.765L7 18"/><path d="M8 18a5 5 0 0 1-5-5V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8"/>'],
        'accessible' => ['en' => 'Accessible', 'de' => 'Barrierefrei', 'svg' => '<circle cx="16" cy="4" r="1"/><path d="m18 19 1-7-6 1"/><path d="m5 8 3-3 5.5 3-2.36 3.5"/><path d="M4.24 14.5a5 5 0 0 0 6.88 6"/><path d="M13.76 17.5a5 5 0 0 0-6.88-6"/>'],
        'hospital' => ['en' => 'Hospital', 'de' => 'Krankenhaus', 'svg' => '<path d="M12 6v4"/><path d="M14 14h-4"/><path d="M14 18h-4"/><path d="M14 8h-4"/><path d="M18 12h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h2"/><path d="M18 22V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v18"/>'],
        'museum' => ['en' => 'Museum', 'de' => 'Museum', 'svg' => '<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>'],
        'church' => ['en' => 'Church', 'de' => 'Kirche', 'svg' => '<path d="M10 9h4"/><path d="M12 7v5"/><path d="M14 22v-4a2 2 0 0 0-4 0v4"/><path d="M18 22V5.618a1 1 0 0 0-.553-.894l-4.553-2.277a2 2 0 0 0-1.788 0L6.553 4.724A1 1 0 0 0 6 5.618V22"/><path d="m18 7 3.447 1.724a1 1 0 0 1 .553.894V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9.618a1 1 0 0 1 .553-.894L6 7"/>'],
        'photo' => ['en' => 'Photo spot', 'de' => 'Fotospot', 'svg' => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>'],
        'park' => ['en' => 'Park', 'de' => 'Park', 'svg' => '<path d="M10 10v.2A3 3 0 0 1 8.9 16H5a3 3 0 0 1-1-5.8V10a3 3 0 0 1 6 0Z"/><path d="M7 16v6"/><path d="M13 19v3"/><path d="M12 19h8.3a1 1 0 0 0 .7-1.7L18 14h.3a1 1 0 0 0 .7-1.7L16 9h.2a1 1 0 0 0 .8-1.7L13 3l-1.4 1.5"/>'],
        'mountain' => ['en' => 'Mountain', 'de' => 'Berg', 'svg' => '<path d="m8 3 4 8 5-5 5 15H2L8 3z"/>'],
        'swimming' => ['en' => 'Swimming', 'de' => 'Baden', 'svg' => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>'],
        'home' => ['en' => 'Home', 'de' => 'Zuhause', 'svg' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>'],
        'star' => ['en' => 'Highlight', 'de' => 'Highlight', 'svg' => '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>'],
        'heart' => ['en' => 'Favourite', 'de' => 'Favorit', 'svg' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>'],
        'flag' => ['en' => 'Flag', 'de' => 'Fahne', 'svg' => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" x2="4" y1="22" y2="15"/>'],
    ];

    /**
     * All available symbols: the built-in ones merged with the `icons` option.
     * A custom symbol is ['label' => 'Name' or ['en' => …, 'de' => …], 'svg' => '<svg …>'],
     * false removes a built-in symbol.
     *
     * @return array<string, array{label: array<string, string>, svg: string}>
     */
    public static function all(): array
    {
        $icons = [];
        foreach (self::BUILT_IN as $name => $icon) {
            $icons[$name] = [
                'label' => ['en' => $icon['en'], 'de' => $icon['de']],
                'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icon['svg'] . '</svg>',
            ];
        }

        foreach ((array)option('tearoom1.leaflet-map.icons', []) as $name => $icon) {
            if ($icon === false) {
                unset($icons[$name]);
                continue;
            }

            if (is_array($icon) && is_string($icon['svg'] ?? null)) {
                $label = $icon['label'] ?? $name;
                $icons[$name] = [
                    'label' => is_array($label) ? $label : ['en' => (string)$label],
                    'svg'   => $icon['svg'],
                ];
            }
        }

        return $icons;
    }

    public static function exists(?string $name): bool
    {
        return $name !== null && $name !== '' && isset(self::all()[$name]);
    }

    public static function svg(string $name): ?string
    {
        return self::all()[$name]['svg'] ?? null;
    }

    public static function label(string $name, ?string $language = null): string
    {
        $labels = self::all()[$name]['label'] ?? [];

        return $labels[$language ?? self::language()] ?? $labels['en'] ?? reset($labels) ?: $name;
    }

    /**
     * Symbols for the panel field, labeled in the language of the user.
     *
     * @return array<int, array{value: string, label: string, svg: string}>
     */
    public static function options(): array
    {
        $language = kirby()->user()?->language() ?? 'en';
        $options  = [];
        foreach (self::all() as $name => $icon) {
            $options[] = ['value' => $name, 'label' => self::label($name, $language), 'svg' => $icon['svg']];
        }

        return $options;
    }

    protected static function language(): string
    {
        return kirby()->language()?->code() ?? kirby()->user()?->language() ?? 'en';
    }
}
