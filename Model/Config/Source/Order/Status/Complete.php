<?php
namespace SDM\Valitor\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

class Complete extends Status
{

    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_COMPLETE,
        Order::STATE_CLOSED,
    ];
}
