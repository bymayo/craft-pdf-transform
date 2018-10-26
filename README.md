<img src="https://github.com/bymayo/craft-pdf-transform/blob/master/resources/icon.jpg" width="70">

# PDF Transform for Craft 3.x

PDF Transform is a Craft CMS plugin that transforms a PDF stored in Assets, to an image. This can then be output via Twig in to a template.

A use case for this is to show the preview of a PDF before a user downloads that particular file.

## Install

-   Install with Composer via `composer require bymayo/pdf-transform` from your project directory
-   Install the plugin in the Craft Control Panel under `Settings > Plugins`

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `PDF Transform`.

## Requirements

- Craft CMS 3.x
- Imagick / Ghostscript 

## Configuration

<table>
	<tr>
		<td><strong>Setting</strong></td>
    <td><strong>Default</strong></td>
		<td><strong>Description</strong></td>
	</tr>
	<tr>
		<td>Page Number</td>
    <td>1</td>
    <td>Set with page should in the PDF should be converted to an image.</td>
	</tr>
  <tr>
		<td>Image Volume</td>
    <td>null</td>
    <td>Choose where converted images should be stored.</td>
	</tr>
  <tr>
		<td>Image Resolution</td>
    <td>72</td>
    <td>Set the resolution of the converted image.</td>
	</tr>
  <tr>
		<td>Image Format</td>
    <td>jpg</td>
    <td>Set the file format of the converted image.</td>
	</tr>
  <tr>
		<td>Image Quality</td>
    <td>100</td>
    <td>Set the image quality of the converted image.</td>
	</tr>
</table>

## Templating

To transform a PDF to an image, and then output the URL use the following Twig tag:

```
{% set asset = entry.asset.one() %}
{{ craft.pdfTransform.url(asset) }}
```

Be aware that this may output a large image, so we'd recommend running this through an image transform. See <a href="#dimensions">Dimensions</a>.

## Known Issues

### Imagick / Ghostscript

The plugin runs PDFs through a PDF library called <a href="https://github.com/spatie/pdf-to-image" target="_blank">pdf-to-image</a>. They have known issues with Imagick where transforms may fail if Ghost Script isn't accessible through Imagick (Very easily resolvable)

Read more about this issue - <https://github.com/spatie/pdf-to-image#issues-regarding-ghostscript>.

### Dimensions

PDF Transform does the basic job of converting your PDF to a single image. It will never be it's role to set width and height dimensions (Other than Image Resolution). 

I'd recommend running the PDF image through one of the following options/plugins and setting the dimensions that way (Some of these also handle caching the image as well)

-   <a href="https://docs.craftcms.com/v2/image-transforms.html" target="_blank">Image Transforms by Craft</a>
-   <a href="https://github.com/aelvan/Imager-Craft" target="_blank">Imager by aelvan</a>
-   <a href="https://github.com/nystudio107/craft-imageoptimize" target="_blank">Image Optimize by nystudio107</a>

### Local

Currently the plugin has only been tested with local assets, not assets through Amazon S3 etc. It may, or may not work with remote assets.

## Support

If you have any issues (Surely not!) then I'll aim to reply to these as soon as possible. If it's a site-breaking-oh-no-what-has-happened moment, then hit me up on the Craft CMS Slack - @bymayo

## Roadmap

- Index assets with the chosen asset volume (Currently only saves the file to the directory)
- Option to generate the image when a PDF asset is uploaded, and run as a task. (Speeds up templating issues)
- Optional variables (E.g. page, resolution etc)
- Test and support remote assets
- When PDF assets are updated, ensure old transformed image is removed.
