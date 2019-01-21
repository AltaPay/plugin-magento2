<?php
namespace SDM\Altapay\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

class Processing extends Status
{

    /**
     * @var string[]
     */
    protected $_stateStatuses = [
        Order::STATE_PROCESSING,
    ];
}
