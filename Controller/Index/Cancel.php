<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use SDM\Altapay\Model\Handler\CreatePaymentHandler;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;

class Cancel extends Action
{
    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var CreatePaymentHandler
     */
    protected $paymentHandler;

    /**
     * ApplePayResponse constructor.
     *
     * @param Context         $context
     * @param Session         $checkoutSession
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Order $order,
        CreatePaymentHandler $paymentHandler
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->order            = $order;
        $this->paymentHandler   = $paymentHandler;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();
        $order = $this->order->load($orderId);
        $this->paymentHandler->setCustomOrderStatus($order, Order::STATE_CANCELED, 'cancel');
        $order->addStatusHistoryComment("ApplePay payment status - ". $order->getStatus());
        $order->setIsNotified(false);
        $order->getResource()->save($order);
    }
}