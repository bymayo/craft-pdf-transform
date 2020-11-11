<?php
/**
 * PDF Transform plugin for Craft CMS 3.x
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\assetbundles\PdfTransform;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    ByMayo
 * @package   PdfTransform
 * @since     1.0.0
 */
class PdfTransformAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@bymayo/pdftransform/assetbundles/PdfTransform/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/PdfTransform.js',
        ];

        $this->css = [
            'css/PdfTransform.css',
        ];

        parent::init();
    }
}
