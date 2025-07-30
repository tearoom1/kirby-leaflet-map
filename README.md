# Kirby Leaflet Map

This Kirby plugin adds an interactive map block using Leaflet.js for displaying custom locations and paths.

[![Screenshot](screenshot.jpg)](https://github.com/tearoom1/kirby-leaflet-map)

## Features

- Interactive map block for Kirby's block editor
- Display custom locations with configurable types, icons, colors, and sizes
- 25+ location types (buildings, sights, nature, infrastructure, etc.)
- Create paths between locations with custom colors
- Configure zoom levels (default, min, max)
- Option to show as embedded map or popup with thumbnail
- Tooltips and popups for locations and paths

## Installation

### Download

Download and copy this repository to `/site/plugins/kirby-leaflet-map`.

### Git submodule

```
git submodule add https://github.com/tearoom1/kirby-leaflet-map.git site/plugins/kirby-leaflet-map
```

### Composer

```
composer require tearoom1/kirby-leaflet-map
```

## Usage

Use the block by adding it to you blueprints fieldsets if they are defined:

```yaml
fieldsets:
  - leaflet-map
```


Once installed, the plugin adds a new "Leaflet Map" block to the block editor. You can configure:

1. Map title and description
2. Display mode (embedded or popup with thumbnail)
3. Zoom settings (default, minimum, maximum)
4. Map locations with various properties:
   - Coordinates (latitude, longitude)
   - Type (25+ types available including buildings, parks, restaurants, etc.)
   - Size (1-5)
   - Custom marker color
   - Title and description
   - Tooltip display options
5. Paths between locations with:
   - Custom colors
   - Title and tooltip options

## Available Location Types

The plugin includes the following location types:
- Building
- University
- Library
- Museum
- Restaurant
- Cafe
- Park
- Hospital
- Shop
- Hotel
- Monument
- Theater
- Tourist Attraction
- Sports Facility
- Airport
- Train Station
- Bus Station
- Parking
- Beach
- Mountain
- Lake
- Forest
- Church
- Office
- Residence
- Default

## Example

Here's an example of a map with several locations and a path:

```yaml
# In your block content
mapTitle: MIT Campus Map
mapDescription: Major buildings and landmarks at MIT
defaultZoom: 17
minZoom: 15
maxZoom: 19
displayMode: embedded
mapItems:
  - lat: 42.3601
    lng: -71.0942
    type: university
    size: 2
    color: "#CC0000"
    title: MIT Great Dome
    description: Building 10, the iconic center of MIT's campus
    tooltip: true
  - lat: 42.3615
    lng: -71.0905
    type: library
    color: "#0066CC"
    title: MIT Media Lab
    tooltip: true
paths:
  - title: Infinite Corridor
    color: "#ff0000"
    tooltip: true
    points:
      - lat: 42.3601
        lng: -71.0942
      - lat: 42.3605
        lng: -71.0925
```

## Configuration

The plugin works out of the box with no configuration needed. All settings are controlled via the block editor interface.

## Browser Support

The plugin supports all modern browsers with Leaflet.js compatibility.

## License

MIT

## Credits

- [Mathis Koblin](https://www.tearoom.one)
- Built with [Leaflet.js](https://leafletjs.com/)
- Uses [OpenStreetMap](https://www.openstreetmap.org/) tiles
