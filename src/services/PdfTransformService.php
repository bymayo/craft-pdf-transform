<?php
/**
 * PDF Transform plugin for Craft CMS 3.x
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\services;

use bymayo\pdftransform\PdfTransform;

use Imagick;
use Craft;
use craft\base\Component;
use Yii;
use yii\base\Exception;
use craft\services\Volumes;
use Spatie\PdfToImage\Pdf;
use craft\elements\Asset;
use craft\helpers\Path;
use Spatie\PdfToText\Pdf as PdfToText;
use DonatelloZa\RakePlus\RakePlus;
use craft\helpers\Queue;
use yii\db\Query;


use bymayo\pdftransform\jobs\TransformJob;

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

      $volumesArray = array(
        array(
            'label' => 'Select a volume',
            'value' => null
          )
      );
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

      if ($imageVolumeId) {
        $volume = Craft::$app->getVolumes()->getVolumeById($imageVolumeId);
        return $volume;
      }

      throw new Exception('PDF Transform: No output image volume selected in settings');
   }

   public function getFileName($asset)
   {
      // e.g. filename-12345.jpg
      return $asset->filename . '-' . $asset->id . '.' . $this->settings->imageFormat;
   }

    public function render($asset)
    {

      $volume = $this->getImageVolume();
      $fileName = $this->getFileName($asset);

      if ($volume->fileExists($fileName)) {
        
        $transformedAsset = Asset::find()
          ->volumeId($volume->id)
          ->filename($fileName)
          ->one();

        return $transformedAsset;

      }

      return $this->pdfToImage(
        $asset,
        $this->settings->indexKeywords
      );

    }

    public function pdfToImage($asset, $indexKeywords = false)
    {

      if ($asset->kind != 'pdf') {
        return false;
      }

      $filename = $this->getFileName($asset);
      $volume = $this->getImageVolume();

      try {

        $assetTransformed = Asset::find()
          ->volumeId($volume->id)
          ->filename($filename)
          ->one();

        if (!$assetTransformed) {

          $pathService = Craft::$app->getPath();
          $tempPath = $pathService->getTempPath(true) . '/' . mt_rand(0, 9999999) . '.' . $this->settings->imageFormat;
          file_put_contents($tempPath, file_get_contents($asset->url));
    
          $tempPathTransform = $pathService->getTempPath(true) . '/' . $filename;
    
          $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);
  
          $pdf = new Pdf($tempPath);
  
          $pdf
            ->setPage($this->settings->page)
            ->setResolution($this->settings->imageResolution)
            ->setCompressionQuality($this->settings->imageQuality)
            ->saveImage($tempPathTransform);
  
          $assetTransformed = new Asset();
          $assetTransformed->tempFilePath = $tempPathTransform;
          $assetTransformed->filename = $filename;
          $assetTransformed->folderId = $folder->id;
          $assetTransformed->newFolderId = $folder->id;
          $assetTransformed->kind = 'Image';
          $assetTransformed->title = $asset->title;
          $assetTransformed->avoidFilenameConflicts = true;
          $assetTransformed->setVolumeId($volume->id);
          $assetTransformed->setScenario(Asset::SCENARIO_CREATE);
  
          $assetTransformed->validate();
            
          if (!Craft::$app->getElements()->saveElement($assetTransformed, false))
          {
  
            PdfTransform::log($e->getMessage());
            throw new Exception('PDF Transform: Could not transform PDF to image');
  
          }

        }

        if ($indexKeywords) 
        {
          $this->indexKeywords($asset, $assetTransformed);
        }

        return $assetTransformed;

      }  
      catch (Exception $e) {

        PdfTransform::log($e->getMessage());
        throw new Exception('PDF Transform: Could not transform PDF to image');

      }

    }

    public function getKeywords($asset)
    {

      $row = (new Query())
        ->select('*')
        ->from('pdftransform_keywords')
        ->where(['pdfAssetId' => $asset->id])
        ->one();

      if ($row) {
        return $row['keywords'];
      }

    }

    public function indexKeywords($pdfAsset, $imageAsset)
    {

      $keywords = $this->getKeywordsFromPdf($pdfAsset);

      try {

        $row = (new Query())
          ->select('*')
          ->from('pdftransform_keywords')
          ->where(['pdfAssetId' => $pdfAsset->id])
          ->one();

        if ($row) {

          Craft::$app->getDb()->createCommand()
            ->update('pdftransform_keywords', [
                'keywords' => $keywords
            ], [
                'pdfAssetId' => $pdfAsset->id
            ])
            ->execute();

        }

        else {

          Craft::$app->getDb()->createCommand()
            ->insert('pdftransform_keywords', [
                'pdfAssetId' => $pdfAsset->id,
                'imageAssetId' => $imageAsset->id,
                'keywords' => $keywords
            ])
            ->execute();

        }

        return true;


      }
      catch (Exception $e) {
        PdfTransform::log($e->getMessage());
        throw new Exception('PDF Transform: Could not index keywords');
      }
    

    }

    public function getKeywordsFromPdf($asset)
    {

      $asset = $asset->getCopyOfFile();

      $text = PdfToText::getText($asset);

      // Extract with a minimum char length of 5
      $phrases = RakePlus::create($text, 'en_US', 5)->sort('asc')->get();

      if (count($phrases) > 150) {
          $keywords = array_slice($phrases, 0, 150);
      } else {
          $keywords = $phrases;
      }

      $keywordString = implode(', ', $keywords);

      $sanitizedKeywords = preg_replace('/[^0-9A-Za-z, ]+/', '', $keywordString); // Keep only letters, numbers, commas and spaces
      $sanitizedKeywords = preg_replace('/\s{2,}/', ' ', $sanitizedKeywords); // Remove double spaces

      return $sanitizedKeywords;

    }

    public function transformVolumeFolder($volumeFolder, $indexKeywords = false)
    {

      if (!$volumeFolder) {
        throw new Exception('PDF Transform: No volume selected');
      }

      $assets = Asset::find()
        ->folderId($volumeFolder)
        ->includeSubfolders()
        ->all();

      if (!$assets) {
        throw new Exception('PDF Transform: No assets found in volume');
      }

      $job = new TransformJob([
        'assets' => $assets,
        'indexKeywords' => $indexKeywords
      ]);

      Queue::push($job);

    }



}
