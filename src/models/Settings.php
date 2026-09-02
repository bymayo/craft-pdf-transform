<?php
/**
 * PDF Transform plugin for Craft CMS
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
    public $page = 1;

    /**
     * @var string|int|null Volume UID (or a legacy volume ID, for settings saved before 5.2.0)
     */
    public $imageVolume = null;
    public $imageFormat = 'jpg';
    public $imageResolution = 72;
    public $imageQuality = 100;
    public $cleanFilenames = false;
    public $imageColorspace = 'srgb';
    public $imageDestination = 'root';
    public $imageSubfolder = '';
    /**
     * @var array Volume UIDs (or legacy volume IDs), or ['*'] for all volumes
     */
    public $sourceVolumes = ['*'];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['page', 'imageResolution', 'imageQuality'], 'integer'],
            ['imageVolume', 'required'],
            ['imageFormat', 'in', 'range' => ['jpg', 'png']],
            ['cleanFilenames', 'boolean'],
            ['imageColorspace', 'in', 'range' => ['none', 'srgb', 'rgb', 'cmyk', 'gray']],
            ['imageDestination', 'in', 'range' => ['root', 'subfolder', 'mirror']],
            ['imageSubfolder', 'string'],
            ['sourceVolumes', 'each', 'rule' => ['string']],
        ];
    }
}
