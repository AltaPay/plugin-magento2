<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SDM\Altapay\Model\Transaction as Model;
use SDM\Altapay\Model\ResourceModel\Transaction as ResourceModel;
use SDM\Altapay\Setup\InstallSchema;

class Collection extends AbstractCollection
{

    protected $_idFieldName = InstallSchema::TABLE_IDENTIFIER;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
