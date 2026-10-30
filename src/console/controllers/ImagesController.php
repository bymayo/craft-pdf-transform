<?php
/**
 * PDF Transform plugin for Craft CMS
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\console\controllers;

use bymayo\pdftransform\PdfTransform;

use craft\console\Controller;
use yii\console\ExitCode;

/**
 * Manages generated PDF images.
 *
 * @author    ByMayo
 * @package   PdfTransform
 * @since     5.3.0
 */
class ImagesController extends Controller
{
    /**
     * Regenerates every existing image with the current settings. Useful after
     * changing settings in `config/pdf-transform.php`.
     */
    public function actionRegenerate(): int
    {
        $regenerated = PdfTransform::$plugin->pdfTransformService->regenerateAll(
            function(int $done, int $total) {
                $this->stdout("\r$done / $total");
            }
        );

        $this->stdout(PHP_EOL . "Regenerated $regenerated image(s)." . PHP_EOL);

        return ExitCode::OK;
    }
}
