<?php
/**
 * PDF Transform plugin for Craft CMS 3.x
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\services;

use bymayo\pdftransform\PdfTransform;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use Yii;
use craft\services\Volumes;
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

    public function __construct() {

      $this->settings = PdfTransform::$plugin->getSettings();
      
    }

    public function getVolumeOptions()
    {

      $volumesArray = array();
      $volumes = new Volumes;

      foreach ($volumes->getAllVolumes() as $volume) {

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
      $volume = Craft::$app->getVolumes()->getVolumeById($imageVolumeId);
      return $volume;
   }

   public function getImageFs()
   {
     $volume = $this->getImageVolume();
     $fs = $volume->getFs();
     return $fs;
  }

    public function getImagePath($asset, $aliasType)
    {

      return Yii::getAlias(App::parseEnv($this->getImageFs()->$aliasType)) . '/' . $this->getFileName($asset);

    }

   public function getFileName($asset)
   {
      // e.g. filename-12345.jpg
      return $asset->filename . '-' . $asset->id . '.' . $this->settings->imageFormat;
   }

    public function url($asset)
    {

      //  @TODO: Check to see if asset exists using actual Asset volume path from volume settings
      if (!file_exists($this->getImagePath($asset, 'path')))
      {

        $this->pdfToImage(
          $asset
        );

      }

      // Get Asset Path
      // @TODO: Check to see if asset exists using actual Asset volume path from volume settings
      return $this->getImagePath($asset, 'url');

    }

    public function getPdfAssetPath($asset)
    {

      $volumePath = Yii::getAlias(App::parseEnv($asset->getVolume()->getFs()->path));
      $folderPath = $asset->getPath();

      $assetPath = $volumePath . '/' . $folderPath;

      return $assetPath;

    }

    public function pdfToImage($asset)
    {

      // @TODO: Check to see if file exists
      $pdf = new Pdf($this->getPdfAssetPath($asset));

      $pdf
        ->setPage($this->settings->page)
        ->setResolution($this->settings->imageResolution)
        ->setCompressionQuality($this->settings->imageQuality)
        ->saveImage($this->getImagePath($asset, 'path'));

      $this->indexAsset($asset);

      return true;

    }

    public function indexAsset($asset)
    {

      $volume = $this->getImageVolume();
      $fs = $this->getImageFs();
      $fileName = $this->getFileName($asset);

      if ($fs->fileExists($fileName)) {

        $assetIndexer = Craft::$app->getAssetIndexer();
        $session = $assetIndexer->createIndexingSession([$volume]);

        Craft::$app->getAssetIndexer()->indexFile(
          $volume,
          $fileName,
          $session->id
        );

      }

   }

}
