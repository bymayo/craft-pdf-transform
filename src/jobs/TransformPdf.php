<?php
/**
 * PDF Transform plugin for Craft CMS
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\jobs;

use bymayo\pdftransform\PdfTransform;

use Craft;
use craft\elements\Asset;
use craft\queue\BaseJob;

/**
 * Converts a newly uploaded PDF to an image.
 *
 * @author    ByMayo
 * @package   PdfTransform
 * @since     5.3.0
 */
class TransformPdf extends BaseJob
{
    /**
     * @var int
     */
    public int $assetId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $asset = Asset::find()
            ->id($this->assetId)
            ->status(null)
            ->one();

        if (!$asset) {
            PdfTransform::log('Skipping auto-transform for asset #' . $this->assetId . ': asset no longer exists.', \yii\log\Logger::LEVEL_INFO);
            return;
        }

        // render() returns the existing image if a template got there first
        PdfTransform::$plugin->pdfTransformService->render($asset);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('pdf-transform', 'Transforming PDF');
    }
}
