<?php
/**
 * Created by PhpStorm.
 * User: simion
 * Date: 10/19/18
 * Time: 2:57 PM
 */

namespace SDM\Altapay\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        //Add a new attribute for the redirect to the payment form
        $setup->startSetup();
        $orderTable = 'sales_order';
        $columnName = 'valitor_payment_form_url';
        $oldColumnName = 'altapay_payment_form_url';
        if (!$setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $columnName)) {
            if ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnName)) {
                $setup->getConnection()->changeColumn(
                    $setup->getTable('sales_order'),
                    'altapay_payment_form_url',
                    'valitor_payment_form_url',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 655366,
                        'nullable' => true,
                        'visible' => false,
                        'comment' => 'Valitor Payment Form Url',
                    ]

                );

            } else {
                $setup->getConnection()
                    ->addColumn(
                        $setup->getTable($orderTable),
                        $columnName,
                        [
                            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                            'length' => 65536,
                            'nullable' => true,
                            'visible' => false,
                            'comment' => 'Valitor Payment Form Url',
                        ]
                    );
            }
        } elseif ($setup->getConnection()->tableColumnExists($setup->getTable($orderTable), $oldColumnName)) {
                $setup->getConnection()
                    ->dropColumn(
                        $setup->getTable($orderTable),
                        $oldColumnName
                    );
        }
        $setup->endSetup();
    }
}
