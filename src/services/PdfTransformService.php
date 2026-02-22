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
        $volumeArray['value'] = $volume->id;
        array_push($volumesArray, $volumeArray);

      }

      return $volumesArray;

    }

    public function getImageVolume()
    {
      $imageVolumeId = $this->settings->imageVolume;

      if (!$imageVolumeId) {
        return null;
      }

      return Craft::$app->getVolumes()->getVolumeById($imageVolumeId);
   }

   public function getImageFs()
   {
     $volume = $this->getImageVolume();

     if (!$volume) {
       return null;
     }

     return $volume->getFs();
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

     $volume = $this->getImageVolume();

     if (!$volume) {
       PdfTransform::log('Image volume not configured or not found.');
       return null;
     }

     $fileName = $this->getFileName($asset);
     $folder = $this->getDestinationFolder($asset);

     if (!$folder) {
       PdfTransform::log('Destination folder could not be resolved.');
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

   public function pdfToImage(Asset $asset): ?Asset
   {

     $filename = $this->getFileName($asset);
     $volume = $this->getImageVolume();

     if (!$volume) {
       PdfTransform::log('Image volume not configured or not found.');
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
       PdfTransform::log('Destination folder could not be resolved for volume #' . $volume->id);
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
       PdfTransform::log('PDF conversion failed for asset #' . $asset->id . ': ' . $e->getMessage());
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

     return null;

   }

}
