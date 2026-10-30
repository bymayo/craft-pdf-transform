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
use craft\queue\BaseJob;

/**
 * Regenerates every existing image with the current settings.
 *
 * @author    ByMayo
 * @package   PdfTransform
 * @since     5.3.0
 */
class RegenerateImages extends BaseJob
{
    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        PdfTransform::$plugin->pdfTransformService->regenerateAll(
            function(int $done, int $total) use ($queue) {
                $this->setProgress($queue, $total ? $done / $total : 1);
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): ?string
    {
        return Craft::t('pdf-transform', 'Regenerating PDF images');
    }
}
