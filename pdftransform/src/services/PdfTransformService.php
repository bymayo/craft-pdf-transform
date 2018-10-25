<?php
/**
 * PDF Transform plugin for Craft CMS 3.x
 *
 * Transform PDF's in to Image
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\services;

use bymayo\pdftransform\PdfTransform;

use Craft;
use craft\base\Component;
use Yii;
use craft\services\Volumes;
use Spatie\PdfToImage\Pdf;

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

    public function getImageLocation($asset, $type)
    {

      $imageVolumeId = $this->settings->imageVolume;
      return Yii::getAlias(Craft::$app->getVolumes()->getVolumeById($imageVolumeId)->$type) . '/' . $asset->id . '.' . $this->settings->imageFormat;

    }

    public function url($asset)
    {

      if (!file_exists($this->getImageLocation($asset, 'path')))
      {

        $this->pdfToImage(
          $asset,
          $this->assetPath($asset)
        );

      }

      return $this->getImageLocation($asset, 'url');

    }

    public function assetPath($asset)
    {

      $volumePath = Yii::getAlias($asset->getVolume()->path);
      $folderPath = $asset->getPath();

      $assetPath = $volumePath . '/' . $folderPath;

      return $assetPath;

    }

    public function pdfToImage($asset, $assetPath)
    {

      $pdf = new Pdf($assetPath);

      $pdf
        ->setPage($this->settings->page)
        ->setResolution($this->settings->imageResolution)
        ->setCompressionQuality($this->settings->imageQuality)
        ->saveImage($this->getImageLocation($asset, 'path'));

    }

    public function saveAsset()
    {

      // $asset = new Asset();
      // $asset->tempFilePath = $tempPath;
      // $asset->filename = $filename;
      // $asset->folderId = 0;
      // $asset->newFolderId = $folder->id;
      // $asset->volumeId = $this->settings->imageVolume;
      // $asset->avoidFilenameConflicts = true;
      // $asset->setScenario(Asset::SCENARIO_CREATE);
      //
      // $result = Craft::$app->getElements()->saveElement($asset);

    }

}
