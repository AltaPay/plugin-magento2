<?php
namespace SDM\Valitor\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use SDM\Valitor\Model\Transaction as Model;
use SDM\Valitor\Model\ResourceModel\Transaction as ResourceModel;
use SDM\Valitor\Setup\InstallSchema;

class Collection extends AbstractCollection
{

    protected $_idFieldName = InstallSchema::TABLE_IDENTIFIER;

    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
