# PDF Transform Changelog

## 1.0.9 - 2022-12-05

> **Note**
> After upgrading ensure you switch on the "Transform PDF's on upload" setting in the plugin settings. Previously this was automatically on, but now defaults to false.

### Added
- Setting to optionally turn on PDF transforms on asset upload

### Fixed
- Added human friendly exception if volume is not set in the plugin settings

## 1.0.8 - 2022-11-14
### Added
- Amazon S3 Compatability
- Servd Compatability (Thanks @mattgrayisok / @servd)
- New `.render()` method that outputs the transformed image as a Craft asset (No longer just the URL 🎉)

## 1.0.7 - 2022-06-17
### Changed
- Icon to work with darkmode themes

## 1.0.6 - 2022-04-13
### Fixed
- Updated Composer dependencies
- Updated Composer to work with Composer 2
- Added PHP 8.0 support with spatie/pdf-to-image package

## 1.0.5 - 2020-11-11
### Fixed
- Source path for asset bundle

## 1.0.4 - 2020-11-06
### Fixed
- Case issue on settings Assets Bundle

## 1.0.3 - 2020-10-29
### Fixed
- Composer 2 Compatibility

## 1.0.2 - 2018-11-15
### Fixed
- Issue where any element that was saved was throwing an error because it wasn't a PDF.

## 1.0.1 - 2018-11-14
### Added
- PDF's are now transformed when any .pdf file is uploaded to the CMS. Speeding up the .url() method
- Transformed PDF's are now indexed in the Asset Volume specified in settings

## 1.0.0 - 2018-10-25
### Added
- Initial release
