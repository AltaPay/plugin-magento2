<?php
/**
 * Created by PhpStorm.
 * User: simion
 * Date: 10/19/18
 * Time: 2:57 PM
 */

namespace SDM\Altapay\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

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
		$setup->startSetup();

		$orderTable = 'sales_order';
		//Add a new attribute for the redirect to the payment form
		$setup->getConnection()
			->addColumn(
				$setup->getTable($orderTable),
				'altapay_payment_form_url',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'length' => 65536,
					'nullable' => true,
					'visible' => false,
					'comment' =>'Altapay Payment Form Url'
				]
			);

		$setup->endSetup();
	}
}