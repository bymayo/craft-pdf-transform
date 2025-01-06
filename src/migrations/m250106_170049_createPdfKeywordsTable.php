<?php

namespace bymayo\pdftransform\migrations;

use Craft;
use craft\db\Migration;

/**
 * m250106_170049_createPdfKeywordsTable migration.
 */
class m250106_170049_createPdfKeywordsTable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->createTable('{{%pdftransform_keywords}}', [
            'id' => $this->primaryKey(),
            'pdfAssetId' => $this->integer()->notNull(),
            'imageAssetId' => $this->integer()->notNull(),
            'keywords' => $this->text()->notNull(),
            'dateCreated' => $this->dateTime(),
            'dateUpdated' => $this->dateTime(),
            'uid' => $this->uid(),
        ]);

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%pdftransform_keywords}}', 'pdfAssetId'),
            '{{%pdftransform_keywords}}',
            'pdfAssetId',
            '{{%assets}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        return true;

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m250106_170049_createPdfKeywordsTable cannot be reverted.\n";
        return false;
    }
}
