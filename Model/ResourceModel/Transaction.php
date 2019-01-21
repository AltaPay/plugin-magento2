<?php
namespace SDM\Altapay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use SDM\Altapay\Setup\InstallSchema;

class Transaction extends AbstractDb
{

    protected function _construct()
    {
        $this->_init(InstallSchema::TABLE_NAME, InstallSchema::TABLE_IDENTIFIER);
    }
}
