<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    const TABLE_NAME = 'sdm_altapay';
    const TABLE_IDENTIFIER = 'id';

    const COLUMN_ID = self::TABLE_IDENTIFIER;
    const COLUMN_ORDERID = 'orderid';
    const COLUMN_TRANSACTION_ID = 'transactionid';
    const COLUMN_PAYMENT_ID = 'paymentid';
    const COLUMN_TRANSACTION_DATA = 'transactiondata';
    const COLUMN_PARAMETERS_DATA = 'parametersdata';
    const COLUMN_CREATED_DATE = 'created_at';

    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        /**
         * Replace database table from sdm_valitor to sdm_altapay
        */
        if ($setup->getConnection()->isTableExists($setup->getTable('sdm_valitor')) == true) {
            $setup->getConnection()->renameTable($setup->getTable('sdm_valitor'), $setup->getTable(self::TABLE_NAME));
        } else {
        // Create transaction data schema
        $table = $installer->getConnection()->newTable($installer->getTable(self::TABLE_NAME));

        $table->addColumn(
            self::TABLE_IDENTIFIER,
            Table::TYPE_INTEGER,
            null,
            [
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true
            ],
            'id'
        );

        $table->addColumn(
            self::COLUMN_ORDERID,
            Table::TYPE_TEXT,
            255,
            [],
            'Order ID'
        );

        $table->addColumn(
            self::COLUMN_TRANSACTION_ID,
            Table::TYPE_TEXT,
            255,
            [],
            'Transaction ID'
        );

        $table->addColumn(
            self::COLUMN_PAYMENT_ID,
            Table::TYPE_TEXT,
            255,
            [],
            'Payment ID'
        );

        $table->addColumn(
            self::COLUMN_TRANSACTION_DATA,
            Table::TYPE_TEXT,
            1024,
            [],
            'Transaction data'
        );

        $table->addColumn(
            self::COLUMN_PARAMETERS_DATA,
            Table::TYPE_TEXT,
            1024,
            [],
            'Parameters data'
        );

        $table->addColumn(
            self::COLUMN_CREATED_DATE,
            Table::TYPE_DATETIME,
            null,
            ['nullable' => false],
            'Created date'
        );

        $table->addIndex(
            $setup->getIdxName(
                self::TABLE_NAME,
                [self::COLUMN_ORDERID]
            ),
            [self::COLUMN_ORDERID]
        );

        $table->addIndex(
            $setup->getIdxName(
                self::TABLE_NAME,
                [self::COLUMN_TRANSACTION_ID]
            ),
            [self::COLUMN_TRANSACTION_ID]
        );

        $table->setComment('Altapay transaction data');
        $installer->getConnection()->createTable($table);
    }
        $installer->endSetup();
    }
}