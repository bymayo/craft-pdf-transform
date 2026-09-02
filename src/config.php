<?php
/**
 * PDF Transform plugin for Craft CMS
 *
 * @link      http://bymayo.co.uk
 * @copyright Copyright (c) 2018 ByMayo
 */

/**
 * PDF Transform config.php
 *
 * This file exists only as a template for the PDF Transform settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'pdf-transform.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // The page number in the PDF to convert to an image.
    'page' => 1,

    // The volume UID (or legacy volume ID) where converted images are stored.
    'imageVolume' => null,

    // Where within the output volume to place generated images.
    // Options: 'root', 'subfolder', 'mirror'
    'imageDestination' => 'root',

    // The subfolder name when imageDestination is set to 'subfolder'.
    'imageSubfolder' => '',

    // The file format of the converted image. Options: 'jpg', 'png'
    'imageFormat' => 'jpg',

    // The resolution of the converted image.
    'imageResolution' => 72,

    // The image quality of the converted image (1-100).
    'imageQuality' => 100,

    // Remove .pdf from output filenames.
    'cleanFilenames' => false,

    // Colour space for the converted image.
    // Options: 'srgb', 'rgb', 'cmyk', 'gray', 'none'
    'imageColorspace' => 'srgb',

    // Which volumes trigger auto-transform on PDF upload.
    // Set to ['*'] for all volumes, or an array of volume UIDs (or legacy volume IDs).
    'sourceVolumes' => ['*'],
];
