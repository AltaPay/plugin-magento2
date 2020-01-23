<?php
namespace SDM\Valitor\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

class Fraud extends Status
{

    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_PAYMENT_REVIEW,
        Order::STATE_HOLDED,
    ];
}
