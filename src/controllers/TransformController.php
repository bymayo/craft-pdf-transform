<?php 

namespace bymayo\pdftransform\controllers;

use craft\web\Controller;

use bymayo\pdftransform\PdfTransform;

use Craft;

class TransformController extends Controller
{

    protected $allowAnonymous = array('transform-pdfs');

    public function actionTransformPdfs()
    {

        $request = Craft::$app->getRequest();

        $volumeFolder = $request->getParam('volumeFolder');
        $indexKeywords = $request->getParam('indexKeywords');
        
        PdfTransform::$plugin->pdfTransformService->transformVolumeFolder($volumeFolder, $indexKeywords);

        return $this->redirectToPostedUrl();
    }
}