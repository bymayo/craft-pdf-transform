<?php
/**
 * PDF Transform plugin for Craft CMS 3.x
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform;

use bymayo\pdftransform\services\PdfTransformService;
use bymayo\pdftransform\utilities\PdfTransformUtility;
use bymayo\pdftransform\variables\PdfTransformVariable;
use bymayo\pdftransform\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\services\Elements;
use craft\events\PluginEvent;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use craft\web\View;
use craft\events\RegisterTemplateRootsEvent;

use yii\base\Event;

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
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    public static function log($message)
    {
      Craft::getLogger()->log($message, \yii\log\Logger::LEVEL_INFO, 'pdf-transform');
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $fileTarget = new \craft\log\FileTarget(
           [
             'logFile' => Craft::getAlias('@storage/logs/pdfTransform.log'),
            'categories' => ['pdf-transform']
            ]
         );

        Craft::getLogger()->dispatcher->targets[] = $fileTarget;

        Craft::setAlias('@pdf-transform', __DIR__);

        $this->setComponents([
            'pdfTransform' => PdfTransformService::class
        ]);

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
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function(Event $event)   {

                $element = $event->element;

                if ($element instanceof \craft\elements\Asset) {
                    if ($event->isNew && $element->extension === 'pdf' && $this->getSettings()->transformPdfsOnUpload) {
                        PdfTransform::$plugin->pdfTransformService->pdfToImage($element, $this->settings->indexKeywords);
                    }
                }

            }
        );

        Event::on(
            View::class, 
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, 
            function (RegisterTemplateRootsEvent $event) {
                if (is_dir($baseDir = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates')) {
                    $event->roots[$this->id] = $baseDir;
                }
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = PdfTransformUtility::class;
            }
        );

    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {

        return Craft::$app->view->renderTemplate(
            'pdf-transform/settings',
            [
                'settings' => $this->getSettings(),
                'volumes' => PdfTransform::$plugin->pdfTransformService->getVolumeOptions()
            ]
        );

    }
}
