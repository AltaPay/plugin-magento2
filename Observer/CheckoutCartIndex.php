<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2020 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;
use Magento\CatalogInventory\Api\StockManagementInterface;
use SDM\Valitor\Model\SystemConfig;
use Magento\Framework\Session\SessionManagerInterface;
use SDM\Valitor\Model\ConstantConfig;

class CheckoutCartIndex implements ObserverInterface
{

    /** @var \Magento\Checkout\Model\Session */
    private $session;

    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Magento\Sales\Model\OrderFactory */
    protected $orderFactory;

    /**
     * @var Coupon
     */
    private $coupon;
    /**
     * @var CouponUsage
     */
    private $couponUsage;

    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param Coupon $coupon
     * @param CouponUsage $couponUsage
     * @param StockManagementInterface $stockManagement
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Coupon $coupon,
        CouponUsage $couponUsage,
        StockManagementInterface $stockManagement,
        SystemConfig $systemConfig
    ) {
        $this->session = $session;
        $this->quoteFactory = $quoteFactory;
        $this->messageManager = $messageManager;
        $this->orderFactory   = $orderFactory;
        $this->coupon          = $coupon;
        $this->couponUsage     = $couponUsage;
        $this->stockManagement = $stockManagement;
        $this->systemConfig    = $systemConfig;
    }


    /**
     * @param Observer $observer
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->session->getValitorCustomerRedirect()) {
            $order = $this->session->getLastRealOrder();
            $quote = $this->quoteFactory->create()->load($order->getQuoteId());
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            $statusHistoryItem = $order->getStatusHistoryCollection()->getFirstItem();
            $errorCodeMerchant = $statusHistoryItem->getData('comment');
            
            $historyComment = __(ConstantConfig::BROWSER_BK_BUTTON_COMMENT);
            $browserBackbtn = false;

            if (strpos($errorCodeMerchant, 'failed') !== false || strpos($errorCodeMerchant, 'error') !== false
            || strpos($errorCodeMerchant, 'cancelled') !== false) {
				  $consumerError = explode('|',$errorCodeMerchant);
                  $consumerErrorMessage = $consumerError[1];
                  $merchantErrorMessage = $consumerError[2];
                  //Displays merchant error message
                if($consumerErrorMessage == $merchantErrorMessage){
                    $historyComment = $consumerErrorMessage;
                }
                else{
                    $historyComment = $merchantErrorMessage." - ".$consumerErrorMessage;
                }
                //Display consumer error messages
                  $message = $consumerError[1];
                  if($message == __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_MERCHANT)){
                    $message = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_CONSUMER);
                    $historyComment = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_CONSUMER);
                  }
                //show fail message
                $this->messageManager->addErrorMessage($message);
            }else{
                $browserBackbtn = true;
            }

            $orderStatusBefore = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
            $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);
            $orderStatusCancelUpdate = Order::STATE_CANCELED;
			$orderStateCancelUpdate = Order::STATE_CANCELED;

            if ($quote->getId() && $this->verifyIfOrderStatus($orderStatusBefore, $order->getStatus(), $orderStatusCancel)) {
              //get quote Id from order and set as active
                $quote->setIsActive(1)->setReservedOrderId(null)->save();
                $this->session->replaceQuote($quote)->unsLastRealOrderId();
           

                if ($order->getCouponCode()) {
                    $this->resetCouponAfterCancellation($order);
                }

              //revert quantity when cancel order
                $orderItems = $order->getAllItems();
                foreach ($orderItems as $item) {
                    $children = $item->getChildrenItems();
                    $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
                    if ($item->getId() && $item->getProductId() && empty($children) && $qty) {
                        $this->stockManagement->backItemQty($item->getProductId(), $qty, $item->getStore()->getWebsiteId());
                    }
                }
                
                if($orderStatusCancel){
				  $orderStatusCancelUpdate = $orderStatusCancel;
				}

                if ($browserBackbtn == true) {
                  //set order status and comments
                    $order->addStatusHistoryComment($historyComment, $orderStatusCancelUpdate);
                    $message = __(ConstantConfig::BROWSER_BK_BUTTON_MSG);
                    $this->messageManager->addErrorMessage($message);
                }

				$order->setState($orderStateCancelUpdate)->setStatus($orderStatusCancelUpdate);
                $order->setIsNotified(false);
                $order->getResource()->save($order);
            }
            $this->session->unsValitorCustomerRedirect();
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @throws \Exception
     */
    public function resetCouponAfterCancellation($order)
    {
        $this->coupon->load($order->getCouponCode(), 'code');
        if ($this->coupon->getId()) {
            $this->coupon->setTimesUsed($this->coupon->getTimesUsed() - 1);
            $this->coupon->save();
            $customerId = $order->getCustomerId();
            if ($customerId) {
                $this->couponUsage->updateCustomerCouponTimesUsed($customerId, $this->coupon->getId(), false);
            }
        }
    }
    
     /**
     * @param orderStatusConfig
     * @param currentOrderStatus
     */
    public function verifyIfOrderStatus($orderStatusConfigBefore, $currentOrderStatus, $orderStatusConfigCancel)
    {
		if(!is_null($orderStatusConfigBefore)){
			if($orderStatusConfigBefore == $currentOrderStatus){
				return true;
			}
		}
		
		if(!is_null($orderStatusConfigCancel)){
			if($orderStatusConfigCancel == $currentOrderStatus){
				return true;
			}
		}

		return false;
    }
}
