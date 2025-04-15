# Changelog 

[Unreleased changes](https://github.com/justbetter/statamic-glide-directive/compare/2.6.5...2.6.5)
## [2.6.5](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.6.5) - 2025-04-15

### Fixed

- Fix empty srcset w rows (#19)

## [2.6.4](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.6.4) - 2025-04-11

### Fixed

- Handle the Laravel requirement through the Statamic requirement (761528f)

## [2.6.3](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.6.3) - 2025-04-08

### Fixed

- Only use a picture tag when the threshold width matches (#18)

## [2.6.2](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.6.2) - 2025-02-19

### Fixed
- Check for parent node before using it by @Jade-GG in https://github.com/justbetter/statamic-glide-directive/pull/17

## [2.6.1](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.6.1) - 2024-12-24

### Fixed
- Fix order of parameters (#16)

## [2.6.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.6.0) - 2024-12-11

### Changed
- Added tests (#11)
- Load lazy to fix safari loading, also grab different width when devicePixelContentBoxSize is not available (#15)

### Fixed
- Fix config publish path (#14)

## [2.5.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.5.0) - 2024-11-21

### Fixed
* Fallback if entry is missing (#13) 

## [2.4.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.4.0) - 2024-11-21

### Added
* Prevent render blocking resizes (#10)
* Added queue setting for image job (#12)


## [2.3.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.3.0) - 2024-10-22

### Changed
* PHPunit test (#4)

### Fixed
* Reduce Forced reflows (#9)

## [2.2.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.2.0) - 2024-10-16

### Changed
* Generate glide presets through jobs in (#8) 

## [2.1.3](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.1.3) - 2024-10-09

### Refactor
- Refactor getPresetsByRatio (#7)

## [2.1.2](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.1.2) - 2024-10-02

### Fixed
- Return empty string when argument is not an asset  

## [2.1.1](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.1.1) - 2024-09-27

### Fixed
- Return empty string when image is null (#6)

## [2.1.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.1.0) - 2024-09-25

### Changed

- Use right config (#5)

## [2.0.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/2.0.0) - 2024-08-08

### Changed
- Statamic 5 compatible (#3)

## [1.2.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/1.2.0) - 2024-06-20

### Added
- Added actions for phpstan, pint and changelogger (#2)


## [1.1.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/1.1.0) - 2024-05-31

### Added
- Added attribute bag + option for sources in config (#1)

## [1.0.2](https://github.com/justbetter/statamic-glide-directive/releases/tag/1.0.2) - 2024-05-17

### Changed
- Use image alts from Statamic

## [1.0.1](https://github.com/justbetter/statamic-glide-directive/releases/tag/1.0.1) - 2024-05-17

### Added
- Added focal points

## [1.0.0](https://github.com/justbetter/statamic-glide-directive/releases/tag/1.0.0) - 2024-05-17

Initial release

