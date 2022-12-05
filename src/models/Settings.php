<?php
/**
 * PDF Transform plugin for Craft CMS 3.x
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

namespace bymayo\pdftransform\models;

use bymayo\pdftransform\PdfTransform;

use Craft;
use craft\base\Model;

/**
 * @author    ByMayo
 * @package   PdfTransform
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $transformPdfsOnUpload = false;
    public $imageVolume = null;
    public $page = 1;
    public $imageFormat = 'jpg';
    public $imageResolution = 72;
    public $imageQuality = 100;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['page', 'imageVolume', 'imageResolution', 'imageQuality'], 'integer'],
            ['transformPdfsOnUpload', 'boolean'],
            ['imageFormat', 'string']
        ];
    }
}
