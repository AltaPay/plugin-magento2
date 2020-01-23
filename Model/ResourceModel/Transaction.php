<?php
namespace SDM\Valitor\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use SDM\Valitor\Setup\InstallSchema;

class Transaction extends AbstractDb
{

    protected function _construct()
    {
        $this->_init(InstallSchema::TABLE_NAME, InstallSchema::TABLE_IDENTIFIER);
    }
}
