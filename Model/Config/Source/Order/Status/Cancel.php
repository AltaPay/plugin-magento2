<?php
namespace SDM\Altapay\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

class Cancel extends Status
{

    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_CANCELED,
    ];
}
