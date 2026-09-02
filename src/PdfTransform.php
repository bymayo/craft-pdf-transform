<?php
/**
 * PDF Transform plugin for Craft CMS
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform;

use bymayo\pdftransform\services\PdfTransformService as PdfTransformServiceService;
use bymayo\pdftransform\variables\PdfTransformVariable;
use bymayo\pdftransform\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\elements\Asset;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;

use craft\events\ModelEvent;
use yii\base\Event;
use yii\log\FileTarget;

/**
 * Class PdfTransform
 *
 * @author    ByMayo
 * @package   PdfTransform
 * @since     1.0.0
 *
 * @property  PdfTransformServiceService $pdfTransformService
 */
class PdfTransform extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var PdfTransform
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    public static function log($message, $level = \yii\log\Logger::LEVEL_ERROR)
   {
      Craft::getLogger()->log($message, $level, 'pdf-transform');
   }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $fileTarget = new FileTarget(
            [
              'logFile' => Craft::getAlias('@storage/logs/pdfTransform.log'),
              'categories' => ['pdf-transform'],
              'logVars' => []
            ]
        );
 
        Craft::getLogger()->dispatcher->targets[] = $fileTarget;

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('pdfTransform', PdfTransformVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );

        Craft::info(
            Craft::t(
                'pdf-transform',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        Event::on(
            Asset::class,
            Asset::EVENT_AFTER_SAVE,
            function(ModelEvent $event) {

                $asset = $event->sender;

               if ($event->isNew && $asset->extension === 'pdf') {

                  $service = PdfTransform::$plugin->pdfTransformService;

                  try {
                    if (!$service->isSourceVolume($asset)) {
                      PdfTransform::log('Skipping auto-transform for asset #' . $asset->id . ': volume not in allowed source volumes.', \yii\log\Logger::LEVEL_INFO);
                      return;
                    }

                    $asset->getStream();
                  } catch (\Throwable $e) {
                    PdfTransform::log('Skipping auto-transform for asset #' . $asset->id . ': ' . get_class($e) . ': ' . $e->getMessage());
                    return;
                  }

                  $service->pdfToImage($asset);
               }

            }
        );

    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {

        $settings = $this->getSettings();
        $service = PdfTransform::$plugin->pdfTransformService;

        return Craft::$app->view->renderTemplate(
            'pdf-transform/settings',
            [
                'settings' => $settings,
                'volumes' => $service->getVolumeOptions(),
                'imageVolumeValue' => $service->normalizeVolumeValue($settings->imageVolume),
                'sourceVolumeValues' => $service->normalizeVolumeValues($settings->sourceVolumes),
                'environmentStatus' => $service->getEnvironmentStatus()
            ]
        );

    }
}
