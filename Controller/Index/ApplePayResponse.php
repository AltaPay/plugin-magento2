<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use SDM\Altapay\Model\Generator;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Math\Random;

class ApplePayResponse extends Action
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * ApplePayResponse constructor.
     *
     * @param Context         $context
     * @param Session         $checkoutSession
     * @param Order           $orderRepository
     * @param Gateway         $gateway
     * @param RedirectFactory $redirectFactory
     * @param Order           $order
     * @param Random          $random
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Order $orderRepository,
        Generator $gateway,
        RedirectFactory $redirectFactory,
        Order $order,
        Random $random
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->gateway          = $gateway;
        $this->redirectFactory  = $redirectFactory;
        $this->order            = $order;
        $this->random           = $random;
        $this->_orderRepository = $orderRepository;
    }

    public function execute()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();
        if ($this->checkPost()) {
            $params = $this->gateway->createRequestApplepay(
                $this->getRequest()->getParam('paytype'),
                $orderId,
                $this->getRequest()->getParam('providerData')
            );

            echo json_encode($params);
        }
    }

    /**
     * @return mixed
     */
    public function checkPost()
    {
        return $this->getRequest()->isPost();
    }

}