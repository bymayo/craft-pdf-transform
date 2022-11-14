<?php
/**
 * PDF Transform plugin for Craft CMS
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
    public function render($asset)
    {
        return PdfTransform::$plugin->pdfTransformService->render($asset);
    }

    public function url($asset)
    {

        $render = PdfTransform::$plugin->pdfTransformService->render($asset);

        if ($render) {
            return $render->getUrl();
        }

    }
}