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
use bymayo\pdftransform\jobs\RegenerateImages;
use bymayo\pdftransform\jobs\TransformPdf;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\elements\Asset;
use craft\events\ConfigEvent;
use craft\events\PluginEvent;
use craft\helpers\Queue;
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

               if ($event->isNew && !$asset->propagating && $asset->extension === 'pdf') {

                  if (!PdfTransform::$plugin->pdfTransformService->isSourceVolume($asset)) {
                    PdfTransform::log('Skipping auto-transform for asset #' . $asset->id . ': volume not in allowed source volumes.', \yii\log\Logger::LEVEL_INFO);
                    return;
                  }

                  // Convert in the queue, so large PDFs don't slow down (or time out) the upload
                  Queue::push(new TransformPdf([
                    'assetId' => $asset->id,
                  ]));
               }

            }
        );

        Craft::$app->getProjectConfig()
            ->onAdd('plugins.' . $this->handle . '.settings', [$this, 'handleChangedSettings'])
            ->onUpdate('plugins.' . $this->handle . '.settings', [$this, 'handleChangedSettings']);

    }

    /**
     * Regenerates existing images when a setting that changes how they look is
     * updated. Settings that change the filename (format, volume etc.) don't need
     * this, as `render()` creates a new image for the new filename.
     */
    public function handleChangedSettings(ConfigEvent $event): void
    {
        $keys = array_flip(['page', 'imageResolution', 'imageQuality', 'imageColorspace']);
        $defaults = array_intersect_key((new Settings())->toArray(), $keys);

        $old = array_map('strval', array_merge($defaults, array_intersect_key((array) $event->oldValue, $keys)));
        $new = array_map('strval', array_merge($defaults, array_intersect_key((array) $event->newValue, $keys)));

        if ($old != $new) {
            Queue::push(new RegenerateImages());
        }
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
        $sourceVolumeValues = $service->normalizeVolumeValues($settings->sourceVolumes);

        return Craft::$app->view->renderTemplate(
            'pdf-transform/settings',
            [
                'settings' => $settings,
                'volumes' => $service->getVolumeOptions(),
                'imageVolumeValue' => $service->normalizeVolumeValue($settings->imageVolume),
                // Craft only ticks the "All" option when the value is the string '*'
                'sourceVolumeValues' => in_array('*', $sourceVolumeValues, true) ? '*' : $sourceVolumeValues,
                'environmentStatus' => $service->getEnvironmentStatus()
            ]
        );

    }
}
