<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\ResourceModel\Token;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\SDM\Altapay\Model\Token::class, \SDM\Altapay\Model\ResourceModel\Token::class);
    }
}
