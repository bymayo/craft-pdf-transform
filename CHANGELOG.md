# PDF Transform Changelog

## 5.1.1 - 2026-02-20

### Fixed
- File existence check now uses volume path instead of filesystem path, fixing duplicate generation on volumes with subpaths ([#25](https://github.com/bymayo/craft-pdf-transform/issues/25))
- Auto-transform on upload no longer crashes when the asset file is not yet available, fixing corrupt image headers ([#23](https://github.com/bymayo/craft-pdf-transform/issues/23))
- Added null checks for image volume to prevent "getFs() on null" errors when volume is misconfigured ([#18](https://github.com/bymayo/craft-pdf-transform/issues/18))

## 5.1.0 - 2026-02-20

### Added
- New "Clean Filenames" setting to strip the `.pdf` extension from generated filenames (e.g. `document-123.jpg` instead of `document.pdf-123.jpg`). Disabled by default for backward compatibility.

### Fixed
- PDF files are now read via Craft's filesystem API instead of HTTP requests, fixing SSRF risk
- `getVolumeOptions()` now uses `Craft::$app->getVolumes()` instead of instantiating an unconfigured `Volumes` service
- Replaced predictable `mt_rand` temp filenames with `uniqid` to avoid collisions
- Asset save event listener now only fires for assets instead of all element types
- Temporary PDF files are now cleaned up after conversion to prevent disk space leaks
- `render()` now re-creates the image asset if the file exists on disk but the asset record is missing
- `pdfToImage()` now returns `null` explicitly on failure instead of returning void
- `url()` Twig variable now returns `null` explicitly when no render is available
- Added `?Asset` type hints and null guards to `render()` and `url()` methods
- Removed empty CSS/JS asset bundle files that caused unnecessary HTTP requests

### Changed
- `imageFormat` setting is now validated to only allow `jpg` or `png` values

## 5.0.1 - 2024-05-30
### Changed
- Icon to a new shiny (literally) icon

## 5.0.0 - 2024-05-30
### Changed
- Craft 5 compatibility

## 2.0.1 - 2022-11-14
### Added
- Amazon S3 Compatability
- Servd Compatability (Thanks @mattgrayisok / @servd)
- New `.render()` method that outputs the transformed image as a Craft asset (No longer just the URL 🎉)

## 2.0.0 - 2022-06-17
### Changed
- Now requires PHP ^8.0.0.
- Now requires Craft CMS ^4.0.0

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
