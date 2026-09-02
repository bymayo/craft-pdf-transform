<?php
/**
 * PDF Transform plugin for Craft CMS
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\services;

use bymayo\pdftransform\PdfTransform;

use Craft;
use craft\base\Component;
use craft\models\Volume;
use craft\models\VolumeFolder;
use Spatie\PdfToImage\Pdf;
use craft\elements\Asset;

/**
 * @author    ByMayo
 * @package   PdfTransform
 * @since     1.0.0
 */
class PdfTransformService extends Component
{

  private $settings;

    // Public Methods
    // =========================================================================

    public function init(): void {

      parent::init();
      $this->settings = PdfTransform::$plugin->getSettings();

    }

    public function getVolumeOptions()
    {

      $volumesArray = array();

      foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {

        $volumeArray = array();
        $volumeArray['label'] = $volume->name;
        $volumeArray['value'] = $volume->uid;
        array_push($volumesArray, $volumeArray);

      }

      return $volumesArray;

    }

    /**
     * Resolves a stored setting value to a volume. Accepts a volume UID, or a
     * numeric volume ID for backwards compatibility with settings saved before
     * 5.2.0.
     */
    public function resolveVolume($value): ?Volume
    {
      if ($value === null || $value === '') {
        return null;
      }

      if (is_numeric($value)) {
        return Craft::$app->getVolumes()->getVolumeById((int) $value);
      }

      return Craft::$app->getVolumes()->getVolumeByUid((string) $value);
    }

    /**
     * Converts a stored setting value to a volume UID, so legacy ID-based
     * settings select the right option on the settings page.
     */
    public function normalizeVolumeValue($value): ?string
    {
      $volume = $this->resolveVolume($value);

      return $volume?->uid;
    }

    /**
     * @param array $values
     * @return array
     */
    public function normalizeVolumeValues($values): array
    {
      if (!is_array($values)) {
        return ['*'];
      }

      $normalized = [];

      foreach ($values as $value) {
        if ($value === '*') {
          $normalized[] = '*';
          continue;
        }

        $uid = $this->normalizeVolumeValue($value);

        if ($uid !== null) {
          $normalized[] = $uid;
        }
      }

      return $normalized;
    }

    /**
     * Whether the given asset lives in a volume that should be auto-transformed.
     */
    public function isSourceVolume(Asset $asset): bool
    {
      $sourceVolumes = $this->settings->sourceVolumes;

      if (!is_array($sourceVolumes) || in_array('*', $sourceVolumes, true)) {
        return true;
      }

      $volume = $asset->getVolume();

      return in_array($volume->uid, $sourceVolumes, true)
        || in_array((string) $volume->id, $sourceVolumes, true)
        || in_array($volume->id, $sourceVolumes, true);
    }

    public function getImageVolume()
    {
      return $this->resolveVolume($this->settings->imageVolume);
   }

   public function getImageFs()
   {
     $volume = $this->getImageVolume();

     if (!$volume) {
       return null;
     }

     return $volume->getFs();
  }

   /**
    * Returns a human readable reason why PDFs can't be converted in this
    * environment, or null if everything needed is available.
    */
   public function getEnvironmentError(): ?string
   {
     if (!class_exists(\Imagick::class)) {
       return 'The Imagick PHP extension isn’t available to this PHP process. Note that a CLI `php -m` check isn’t enough — it must also be enabled for the PHP-FPM/web SAPI.';
     }

     try {
       $formats = (new \Imagick())->queryFormats('PDF');
     } catch (\Throwable $e) {
       return 'Imagick could not be initialised: ' . $e->getMessage();
     }

     if (empty($formats)) {
       return 'Imagick has no PDF delegate. Ghostscript is either missing, or PDF is blocked by the ImageMagick security policy (policy.xml).';
     }

     return null;
   }

   /**
    * Converts a tiny generated PDF, to prove the whole Imagick/Ghostscript
    * chain works in this exact environment. Used by the settings page.
    *
    * @return array{ok: bool, message: string}
    */
   public function runSelfTest(): array
   {
     $error = $this->getEnvironmentError();

     if ($error !== null) {
       return ['ok' => false, 'message' => $error];
     }

     $path = Craft::$app->getPath()->getTempPath(true) . '/' . uniqid('pdf_selftest_', true) . '.pdf';

     try {
       if (file_put_contents($path, $this->getSelfTestPdf()) === false) {
         return ['ok' => false, 'message' => 'Craft’s temp path isn’t writable: ' . dirname($path)];
       }

       $imagick = new \Imagick();
       $imagick->setResolution(72, 72);
       $imagick->readImage($path . '[0]');
       $imagick->setFormat('jpeg');
       $imagick->getImageBlob();
       $imagick->clear();
     } catch (\Throwable $e) {
       return [
         'ok' => false,
         'message' => get_class($e) . ': ' . $e->getMessage(),
       ];
     } finally {
       @unlink($path);
     }

     return ['ok' => true, 'message' => 'Working'];
   }

   /**
    * Returns a checklist of everything the plugin needs, for the settings page.
    */
   public function getEnvironmentStatus(): array
   {
     $status = [];

     $imagickInstalled = class_exists(\Imagick::class);

     $status[] = [
       'label' => 'Imagick extension',
       'ok' => $imagickInstalled,
       'detail' => $imagickInstalled
         ? $this->getImageMagickVersion()
         : 'Not available to this PHP process',
     ];

     $selfTest = $this->runSelfTest();

     $status[] = [
       'label' => 'PDF conversion',
       'ok' => $selfTest['ok'],
       'detail' => $selfTest['ok'] ? 'Working' : $selfTest['message'],
     ];

     $volume = $this->getImageVolume();

     $status[] = [
       'label' => 'Output image volume',
       'ok' => $volume !== null,
       'detail' => $volume
         ? $volume->name
         : 'Not set, or missing in this environment',
     ];

     return $status;
   }

   public function getDestinationFolder(Asset $asset): ?VolumeFolder
   {
     $volume = $this->getImageVolume();

     if (!$volume) {
       return null;
     }

     $rootFolder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);

     if (!$rootFolder) {
       return null;
     }

     $destination = $this->settings->imageDestination;

     if ($destination === 'subfolder') {
       $subfolder = trim($this->settings->imageSubfolder, '/');
       if ($subfolder === '') {
         return $rootFolder;
       }
       return Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($subfolder . '/', $volume);
     }

     if ($destination === 'mirror') {
       $sourceFolder = $asset->getFolder();
       $sourcePath = $sourceFolder ? $sourceFolder->path : '';
       if ($sourcePath === '' || $sourcePath === null) {
         return $rootFolder;
       }
       return Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($sourcePath, $volume);
     }

     return $rootFolder;
   }

   public function getFileName(Asset $asset): string
   {
      $basename = $this->settings->cleanFilenames
        ? pathinfo($asset->filename, PATHINFO_FILENAME)
        : $asset->filename;

      return $basename . '-' . $asset->id . '.' . $this->settings->imageFormat;
   }

   public function render(?Asset $asset): ?Asset
   {

     if (!$asset) {
       return null;
     }

     try {
       return $this->renderInternal($asset);
     } catch (\Throwable $e) {
       PdfTransform::log('Render failed for asset #' . $asset->id . ': ' . get_class($e) . ': ' . $e->getMessage());
       return null;
     }

   }

   public function pdfToImage(Asset $asset): ?Asset
   {

     try {
       return $this->convert($asset);
     } catch (\Throwable $e) {
       PdfTransform::log('PDF conversion failed for asset #' . $asset->id . ': ' . get_class($e) . ': ' . $e->getMessage());
       return null;
     }

   }

    // Private Methods
    // =========================================================================

   private function renderInternal(Asset $asset): ?Asset
   {

     $volume = $this->getImageVolume();

     if (!$volume) {
       PdfTransform::log('Image volume not configured or not found. Check the “Output Image Volume” setting — volume IDs saved before 5.2.0 may not exist in this environment.');
       return null;
     }

     $fileName = $this->getFileName($asset);
     $folder = $this->getDestinationFolder($asset);

     if (!$folder) {
       PdfTransform::log('Destination folder could not be resolved for volume “' . $volume->name . '”.');
       return null;
     }

     $filePath = ($folder->path ?? '') . $fileName;

     if ($volume->fileExists($filePath)) {

       $transformedAsset = Asset::find()
         ->volumeId($volume->id)
         ->folderId($folder->id)
         ->filename($fileName)
         ->one();

       if ($transformedAsset) {
         return $transformedAsset;
       }

       // File exists on disk but asset record is missing — re-create it
     }

     return $this->pdfToImage(
       $asset
     );

   }

   private function convert(Asset $asset): ?Asset
   {

     $environmentError = $this->getEnvironmentError();

     if ($environmentError !== null) {
       PdfTransform::log('Cannot convert asset #' . $asset->id . '. ' . $environmentError);
       return null;
     }

     $filename = $this->getFileName($asset);
     $volume = $this->getImageVolume();

     if (!$volume) {
       PdfTransform::log('Image volume not configured or not found. Check the “Output Image Volume” setting — volume IDs saved before 5.2.0 may not exist in this environment.');
       return null;
     }

     $pathService = Craft::$app->getPath();
     $tempPath = $pathService->getTempPath(true) . '/' . uniqid('pdf_', true) . '.pdf';
     $stream = $asset->getStream();
     file_put_contents($tempPath, $stream);
     if (is_resource($stream)) {
       fclose($stream);
     }

     $tempPathTransform = $pathService->getTempPath(true) . '/' . $filename;

     $folder = $this->getDestinationFolder($asset);

     if (!$folder) {
       @unlink($tempPath);
       PdfTransform::log('Destination folder could not be resolved for volume “' . $volume->name . '”.');
       return null;
     }

     try {

       $pdf = new Pdf($tempPath);

       $colorspaceMap = [
         'srgb' => \Imagick::COLORSPACE_SRGB,
         'rgb' => \Imagick::COLORSPACE_RGB,
         'cmyk' => \Imagick::COLORSPACE_CMYK,
         'gray' => \Imagick::COLORSPACE_GRAY,
       ];

       $colorspace = $this->settings->imageColorspace;

       if ($colorspace !== 'none' && isset($colorspaceMap[$colorspace])) {
         $pdf->setColorspace($colorspaceMap[$colorspace]);
       }

       $pdf
         ->setPage($this->settings->page)
         ->setResolution($this->settings->imageResolution)
         ->setCompressionQuality($this->settings->imageQuality)
         ->saveImage($tempPathTransform);

     } catch (\Throwable $e) {
       @unlink($tempPath);
       PdfTransform::log('PDF conversion failed for asset #' . $asset->id . ': ' . get_class($e) . ': ' . $e->getMessage());
       return null;
     }

     @unlink($tempPath);

     $assetTransformed = new Asset();
     $assetTransformed->tempFilePath = $tempPathTransform;
     $assetTransformed->filename = $filename;
     $assetTransformed->newLocation = "{folder:" . $folder->id . "}" . $filename;
     $assetTransformed->kind = 'Image';
     $assetTransformed->title = $asset->title;
     $assetTransformed->avoidFilenameConflicts = true;
     $assetTransformed->setVolumeId($volume->id);
     $assetTransformed->setScenario(Asset::SCENARIO_CREATE);

     if (!$assetTransformed->validate()) {
       PdfTransform::log('Asset validation failed for "' . $filename . '": ' . implode(', ', $assetTransformed->getFirstErrors()));
       return null;
     }

     if (Craft::$app->getElements()->saveElement($assetTransformed, false)) {
       return $assetTransformed;
     }

     PdfTransform::log('Could not save the generated image "' . $filename . '" to volume “' . $volume->name . '”: ' . implode(', ', $assetTransformed->getFirstErrors()));

     return null;

   }

   /**
    * Returns just the "ImageMagick 7.1.1-47" part of Imagick's version string.
    */
   private function getImageMagickVersion(): string
   {
     try {
       $version = \Imagick::getVersion()['versionString'] ?? '';
     } catch (\Throwable $e) {
       return 'Available';
     }

     if (preg_match('/^ImageMagick [\d.\-]+/', $version, $match)) {
       return $match[0];
     }

     return $version ?: 'Available';
   }

   /**
    * Builds a valid single-page PDF in memory for the self test.
    */
   private function getSelfTestPdf(): string
   {
     $objects = [
       '<< /Type /Catalog /Pages 2 0 R >>',
       '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
       '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 72 72] /Resources << >> >>',
     ];

     $pdf = "%PDF-1.4\n";
     $offsets = [];

     foreach ($objects as $i => $object) {
       $offsets[] = strlen($pdf);
       $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
     }

     $startXref = strlen($pdf);
     $size = count($objects) + 1;

     $pdf .= "xref\n0 $size\n0000000000 65535 f \n";

     foreach ($offsets as $offset) {
       $pdf .= sprintf("%010d 00000 n \n", $offset);
     }

     $pdf .= "trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$startXref\n%%EOF\n";

     return $pdf;
   }

}
