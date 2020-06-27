<?php
/**
 * PDF Transform plugin for Craft CMS 3.x
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\variables;

use bymayo\pdftransform\PdfTransform;

use Craft;

/**
 * @author    ByMayo
 * @package   PdfTransform
 * @since     1.0.0
 */
class PdfTransformVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
     public function url($asset)
     {
         return PdfTransform::$plugin->pdfTransformService->url($asset);
     }
     public function asset($asset){
         return PdfTransform::$plugin->pdfTransformService->asset($asset);
     }
}
