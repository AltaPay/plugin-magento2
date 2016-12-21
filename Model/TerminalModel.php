<?php
namespace SDM\Altapay\Model;

abstract class TerminalModel extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isOffline = true;

}
