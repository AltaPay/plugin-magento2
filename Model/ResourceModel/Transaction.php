<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use SDM\Altapay\Setup\InstallSchema;

class Transaction extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(InstallSchema::TABLE_NAME, InstallSchema::TABLE_IDENTIFIER);
    }
}
