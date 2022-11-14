<img src="https://github.com/bymayo/craft-pdf-transform/blob/craft-3/resources/icon.png" width="60">

# PDF Transform for Craft CMS 4

PDF Transform is a Craft CMS plugin that transforms a PDF stored in Assets, to an image. This can then be output via Twig in to a template.

A use case for this is to show the preview of a PDF before a user downloads that particular file.
## Features

- Transform PDF's to images via Twig (The file needs to be an existing Asset element)
- PDF's are transformed to an image when PDF's are uploaded via Assets or Asset fields.
- Transformed PDF images are indexed and available in Assets like all other asset elements.
- Works with local asset volumes, Amazon S3 and Servd.

## Install

-  Install with Composer via `composer require bymayo/pdf-transform` from your project directory
-  Enable / Install the plugin in the Craft Control Panel under `Settings > Plugins`
-  Customise the plugin settings, _*especially*_ the `Image Volume` option.

You can also install the plugin via the Plugin Store in the Craft Admin CP by searching for `PDF Transform`.

## Requirements

- Craft CMS 4.x
- Imagick / Ghostscript 
- MySQL (No PostgreSQL support)

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

To transform a PDF to an image use the following Twig tag:

```
{% set pdfToTransform = entry.pdfAsset.one() %}

{% set transformedPdf = craft.pdfTransform.render(pdfToTransform) %}

{{ transformedPdf.one().url }}
```

The transformed PDF (Now an image stored in Assets) can then be output using `{{ transformedPdf.one().url }}`. Or get any Asset property e.g. `title`, `id`, `filename` etc.

If the transformed image doesn't exist then the PDF will be transformed via the template. This may cause the template/page to become slow whilst the PDF is transformed.

Be aware that this also may output a large image, so we'd recommend running this through an image transform. See <a href="#dimensions">Dimensions</a>.

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

## Roadmap

- Optional variables (E.g. page, resolution etc)
- When PDF assets are updated, ensure old transformed image is removed.
