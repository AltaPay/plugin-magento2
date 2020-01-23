<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SDM\Valitor\Model;

/**
 * Pay In Store payment method model
 */
class Terminal4 extends TerminalModel
{

    const METHOD_CODE = 'terminal4';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;
}
