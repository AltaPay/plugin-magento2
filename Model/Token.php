<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Magento\Framework\Model\AbstractModel;

class Token extends AbstractModel
{
    public function _construct()
    {
        $this->_init(ResourceModel\Token::class);
    }
}
