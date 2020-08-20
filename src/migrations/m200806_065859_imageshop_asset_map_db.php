<?php

namespace vangenplotz\imageshop\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200806_065859_imageshop_asset_map_db migration.
 */
class m200806_065859_imageshop_asset_map_db extends Migration
{

    protected $tableName = '{{%imageshop_imageshopasset}}';
    protected $assetIndexTableName = '{{%idx-imageshop_imageshopasset-assetId}}';
    protected $assetForeignKeyName = '{{%fk-imageshop_imageshopasset-assetId}}';

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add mapping table
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'imageshopDocumentInterface' => $this->text()->notNull(),
            'imageshopDocumentLanguage' => $this->text()->notNull(),
            'imageshopDocumentId' => $this->integer()->notNull(),
            'imageshopTitle' => $this->text(),
            'imageshopDescription' => $this->text(),
            'imageshopCredits' => $this->text(),
            'imageshopRights' => $this->text(),
            'imageshopUrl' => $this->text(),
            'imageshopWidth' => $this->integer(),
            'imageshopHeight' => $this->integer(),
            'dateCreated' => $this->dateTime(),
            'dateUpdated' => $this->dateTime(),
            'uid' => $this->char(36)
        ]);

        // creates index for column `assetId`
        $this->createIndex(
            $this->assetIndexTableName,
            $this->tableName,
            'assetId'
        );

        // add foreign key for table `assets`
        $this->addForeignKey(
            $this->assetForeignKeyName,
            $this->tableName,
            'assetId',
            '{{%assets}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // drops foreign key for table `category`
        $this->dropForeignKey(
            $this->assetForeignKeyName,
            $this->tableName
        );

        // drops index for column `category_id`
        $this->dropIndex(
            $this->assetIndexTableName,
            $this->tableName
        );

        $this->dropTable('{{%imageshop_imageshopasset}}');
    }
}
