<?php
namespace SDM\Altapay\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SDM\Altapay\Model\Transaction as Model;
use SDM\Altapay\Model\ResourceModel\Transaction as ResourceModel;
use SDM\Altapay\Setup\InstallSchema;

class Collection extends AbstractCollection
{

    protected $_idFieldName = InstallSchema::TABLE_IDENTIFIER;

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
