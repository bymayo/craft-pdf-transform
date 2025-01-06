<?php

namespace bymayo\pdftransform\jobs;

use bymayo\pdftransform\PdfTransform;

use Craft;
use craft\queue\BaseJob;

class TransformJob extends BaseJob
{

    public $assets;
    public $indexKeywords;

    public function execute($queue): void
    {

        $totalAssets = count($this->assets);

        foreach ($this->assets as $i => $asset) {

            $this->setProgress(
                $queue,
                $i / $totalAssets,
                Craft::t('pdf-transform', '{step, number} of {total, number}', [
                    'step' => $i + 1,
                    'total' => $totalAssets,
                ])
            );

            PdfTransform::$plugin->pdfTransformService->pdfToImage($asset, $this->indexKeywords);
        }
        
    }

    protected function defaultDescription(): string
    {
        return Craft::t('pdf-transform', 'Transforming PDFs to images');
    }
}