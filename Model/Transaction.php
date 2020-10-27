<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use SDM\Altapay\Setup\InstallSchema;

class Transaction extends AbstractModel implements IdentityInterface
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('SDM\Altapay\Model\ResourceModel\Transaction');
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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getData(InstallSchema::TABLE_IDENTIFIER);
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->getData(InstallSchema::COLUMN_ORDERID);
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getData(InstallSchema::COLUMN_TRANSACTION_ID);
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return (string)$this->getData(InstallSchema::COLUMN_PAYMENT_ID);
    }

    /**
     * @return string
     */
    public function getTransactionData()
    {
        return $this->getData(InstallSchema::COLUMN_TRANSACTION_DATA);
    }

    /**
     * @return string
     */
    public function getParametersData()
    {
        return $this->getData(InstallSchema::COLUMN_PARAMETERS_DATA);
    }
}
