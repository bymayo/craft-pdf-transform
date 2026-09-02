<img src="https://github.com/bymayo/craft-pdf-transform/blob/craft-5/resources/icon.png" width="60">

# PDF Transform for Craft CMS 5

PDF Transform is a Craft CMS plugin that transforms a PDF stored in Assets, to an image. This can then be output via Twig in to a template.

A use case for this is to show the preview of a PDF before a user downloads that particular file.
## Features

- Transform PDF's to images via Twig (The file needs to be an existing Asset element)
- PDF's are transformed to an image when PDF's are uploaded via Assets or Asset fields.
- Transformed PDF images are indexed and available in Assets like all other asset elements.
- Works with local asset volumes, Amazon S3 and Servd.
- Control where generated images are placed (volume root, subfolder, or mirror source path).
- Restrict auto-transform to specific source volumes.

## Install

-  Install with Composer via `composer require bymayo/pdf-transform` from your project directory
-  Enable / Install the plugin in the Craft Control Panel under `Settings > Plugins`
-  Customise the plugin settings, _*especially*_ the `Image Volume` option.

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `PDF Transform`.

## Requirements

- Craft CMS 5.x
- Imagick / Ghostscript
- MySQL (No PostgreSQL support)

## Configuration

All settings can be configured from the plugin settings page in the Control Panel, or via a `config/pdf-transform.php` config file.

<table>
	<tr>
		<td><strong>Setting</strong></td>
    <td><strong>Default</strong></td>
		<td><strong>Description</strong></td>
	</tr>
	<tr>
		<td>Page Number</td>
    <td><code>1</code></td>
    <td>Set which page in the PDF should be converted to an image.</td>
	</tr>
  <tr>
		<td>Image Volume</td>
    <td><code>null</code></td>
    <td>Choose which volume converted images should be stored in. Stored as a volume UID, so it survives deploys.</td>
	</tr>
  <tr>
		<td>Output Destination</td>
    <td><code>root</code></td>
    <td>Where within the output volume to place generated images. Options: <code>root</code> (volume root), <code>subfolder</code> (static subfolder), <code>mirror</code> (mirror the source PDF's folder path).</td>
	</tr>
  <tr>
		<td>Subfolder Name</td>
    <td><code>''</code></td>
    <td>When Output Destination is set to <code>subfolder</code>, the name of the subfolder to place images in (e.g. <code>thumbnails</code>).</td>
	</tr>
  <tr>
		<td>Image Resolution</td>
    <td><code>72</code></td>
    <td>Set the resolution of the converted image.</td>
	</tr>
  <tr>
		<td>Image Quality</td>
    <td><code>100</code></td>
    <td>Set the image quality of the converted image.</td>
	</tr>
  <tr>
		<td>Clean Filenames</td>
    <td><code>false</code></td>
    <td>Remove <code>.pdf</code> from output filenames (e.g. <code>document-123.jpg</code> instead of <code>document.pdf-123.jpg</code>).</td>
	</tr>
  <tr>
		<td>Colour Space</td>
    <td><code>srgb</code></td>
    <td>Colour space for the converted image. Options: <code>srgb</code>, <code>rgb</code>, <code>cmyk</code>, <code>gray</code>, <code>none</code>. sRGB fixes inverted colours on CMYK PDFs.</td>
	</tr>
  <tr>
		<td>Image Format</td>
    <td><code>jpg</code></td>
    <td>Set the file format of the converted image. Options: <code>jpg</code>, <code>png</code>.</td>
	</tr>
  <tr>
		<td>Source Volumes</td>
    <td><code>['*']</code></td>
    <td>Which volumes trigger auto-transform when a PDF is uploaded. Set to <code>['*']</code> for all volumes, or an array of volume UIDs. This does not affect the <code>craft.pdfTransform.render()</code> Twig method.</td>
	</tr>
</table>

### Config File

You can override plugin settings by creating a `config/pdf-transform.php` file in your Craft project:

```php
<?php

return [
    'page' => 1,
    'imageVolume' => null,
    'imageDestination' => 'root',
    'imageSubfolder' => '',
    'imageFormat' => 'jpg',
    'imageResolution' => 72,
    'imageQuality' => 100,
    'cleanFilenames' => false,
    'imageColorspace' => 'srgb',
    'sourceVolumes' => ['*'],
];
```

`imageVolume` and `sourceVolumes` accept volume UIDs or IDs.

This supports Craft's [multi-environment config](https://craftcms.com/docs/5.x/configure.html#multi-environment-configs) format.

## Templating

To transform a PDF to an image use the following Twig tag:

```twig
{% set pdfToTransform = entry.pdfAsset.one() %}

{% set transformedPdf = craft.pdfTransform.render(pdfToTransform) %}

{% if transformedPdf %}
    <img src="{{ transformedPdf.url }}">
{% endif %}
```

The transformed PDF (Now an image stored in Assets) can then be output using `{{ transformedPdf.url }}`. Or get any Asset property e.g. `title`, `id`, `filename` etc.

`render()` returns `null` if the PDF can't be converted, so always guard it with `{% if %}` as above — unguarded, Twig throws and the page fails to render.

If the transformed image doesn't exist then the PDF will be transformed via the template. This may cause the template/page to become slow whilst the PDF is transformed.

Be aware that this also may output a large image, so we'd recommend running this through an image transform. See <a href="#dimensions">Dimensions</a>.

## Troubleshooting

### It works locally but not on the server

Check the **Status** panel at the bottom of the plugin settings. It converts a test PDF and reports what's missing.

It runs in the web process on purpose — `php -m` over SSH tests the CLI SAPI, which is often configured differently to PHP-FPM.

### Nothing appears to happen

Failures are logged to `storage/logs/pdfTransform.log` and Craft's `web.log`. If `render()` returns `null`, the log says why.

## Known Issues

### Imagick / Ghostscript

The plugin runs PDFs through a PDF library called <a href="https://github.com/spatie/pdf-to-image" target="_blank">pdf-to-image</a>. They have known issues with Imagick where transforms may fail if Ghost Script isn't accessible through Imagick (Very easily resolvable)

Read more about this issue - <https://github.com/spatie/pdf-to-image#issues-regarding-ghostscript>.

### Dimensions

PDF Transform does the basic job of converting your PDF to a single image. It will never be it's role to set width and height dimensions (Other than Image Resolution).

I'd recommend running the PDF image through one of the following options/plugins and setting the dimensions that way (Some of these also handle caching the image as well)

-   <a href="https://docs.craftcms.com/v2/image-transforms.html" target="_blank">Image Transforms by Craft</a>
-   <a href="https://plugins.craftcms.com/imager-x" target="_blank">Imager X by aelvan</a>
-   <a href="https://plugins.craftcms.com/image-optimize" target="_blank">Image Optimize by nystudio107</a>

## Support

If you have any issues (Surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Discord - @bymayo
