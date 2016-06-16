<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace SDM\Altapay\Model;

/**
 * Pay In Store payment method model
 */
class Terminal1 extends \Magento\Payment\Model\Method\AbstractMethod
{

    const METHOD_CODE = 'terminal1';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;




}
