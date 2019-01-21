<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SDM\Altapay\Model;

/**
 * Pay In Store payment method model
 */
class Terminal3 extends TerminalModel
{

    const METHOD_CODE = 'terminal3';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;
}
