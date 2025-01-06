<?php

namespace bymayo\pdftransform\utilities;

use Craft;
use craft\base\Utility;

class PdfTransformUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('pdf-transform', 'PDF Transform');
    }

    public static function id(): string
    {
        return 'pdf-transform';
    }

    public static function iconPath(): ?string
    {
        return Craft::getAlias('@pdf-transform/icon.svg');
    }

    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();

        return $view->renderTemplate('pdf-transform/utility.twig');
    }
}
