<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2020 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use SDM\Valitor\Setup\InstallSchema;

class Transaction extends AbstractModel implements IdentityInterface
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('SDM\Valitor\Model\ResourceModel\Transaction');
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [InstallSchema::TABLE_NAME . '_' . $this->getId()];
    }

    public function getId()
    {
        return $this->getData(InstallSchema::TABLE_IDENTIFIER);
    }

    public function getOrderId()
    {
        return $this->getData(InstallSchema::COLUMN_ORDERID);
    }

    public function getTransactionId()
    {
        return $this->getData(InstallSchema::COLUMN_TRANSACTION_ID);
    }

    public function getPaymentId()
    {
        return $this->getData(InstallSchema::COLUMN_PAYMENT_ID);
    }

    public function getTransactionData()
    {
        return $this->getData(InstallSchema::COLUMN_TRANSACTION_DATA);
    }

    public function getParametersData()
    {
        return $this->getData(InstallSchema::COLUMN_PARAMETERS_DATA);
    }
}
