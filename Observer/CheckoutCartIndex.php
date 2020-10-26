<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;
use Magento\CatalogInventory\Api\StockManagementInterface;
use SDM\Altapay\Model\SystemConfig;
use Magento\Framework\Session\SessionManagerInterface;
use SDM\Altapay\Model\ConstantConfig;

class CheckoutCartIndex implements ObserverInterface
{

    /** @var Session */
    private $session;

    /** @var QuoteFactory */
    private $quoteFactory;

    /** @var ManagerInterface */
    protected $messageManager;

    /** @var OrderFactory */
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
     * CheckoutCartIndex constructor.
     *
     * @param Context                  $context
     * @param Session                  $session
     * @param ManagerInterface         $messageManager
     * @param QuoteFactory             $quoteFactory
     * @param OrderFactory             $orderFactory
     * @param Coupon                   $coupon
     * @param CouponUsage              $couponUsage
     * @param StockManagementInterface $stockManagement
     * @param SystemConfig             $systemConfig
     */
    public function __construct(
        Context $context,
        Session $session,
        ManagerInterface $messageManager,
        QuoteFactory $quoteFactory,
        OrderFactory $orderFactory,
        Coupon $coupon,
        CouponUsage $couponUsage,
        StockManagementInterface $stockManagement,
        SystemConfig $systemConfig
    ) {
        $this->session         = $session;
        $this->quoteFactory    = $quoteFactory;
        $this->messageManager  = $messageManager;
        $this->orderFactory    = $orderFactory;
        $this->coupon          = $coupon;
        $this->couponUsage     = $couponUsage;
        $this->stockManagement = $stockManagement;
        $this->systemConfig    = $systemConfig;
    }


    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->session->getAltapayCustomerRedirect()) {
            $order             = $this->session->getLastRealOrder();
            $quote             = $this->quoteFactory->create()->load($order->getQuoteId());
            $storeScope        = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode         = $order->getStore()->getCode();
            $statusHistoryItem = $order->getStatusHistoryCollection()->getFirstItem();
            $errorCodeMerchant = $statusHistoryItem->getData('comment');

            $historyComment = __(ConstantConfig::BROWSER_BK_BUTTON_COMMENT);
            $browserBackbtn = false;

            if (strpos($errorCodeMerchant, 'failed') !== false || strpos($errorCodeMerchant, 'error') !== false
                || strpos($errorCodeMerchant, 'cancelled') !== false
            ) {
                $consumerError        = explode('|', $errorCodeMerchant);
                $consumerErrorMessage = $consumerError[1];
                $merchantErrorMessage = $consumerError[2];
                //Displays merchant error message
                if ($consumerErrorMessage == $merchantErrorMessage) {
                    $historyComment = $consumerErrorMessage;
                } else {
                    $historyComment = $merchantErrorMessage . " - " . $consumerErrorMessage;
                }
                //Display consumer error messages
                $message = $consumerError[1];
                if ($message == __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_MERCHANT)) {
                    $message        = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_CONSUMER);
                    $historyComment = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_CONSUMER);
                }
                //show fail message
                $this->messageManager->addErrorMessage($message);
            } else {
                $browserBackbtn = true;
            }

            $orderStatusBefore       = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
            $orderStatusCancel       = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);
            $orderStatusCancelUpdate = Order::STATE_CANCELED;
            $orderStateCancelUpdate  = Order::STATE_CANCELED;

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
                    $qty      = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced())
                                - $item->getQtyCanceled();
                    if ($item->getId() && $item->getProductId() && empty($children) && $qty) {
                        $this->stockManagement->backItemQty($item->getProductId(), $qty,
                            $item->getStore()->getWebsiteId());
                    }
                }

                if ($orderStatusCancel) {
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
            $this->session->unsAltapayCustomerRedirect();
        }
    }

    /**
     * @param Order $order
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
     * @param string $orderStatusConfigBefore
     * @param string $currentOrderStatus
     * @param string $orderStatusConfigCancel
     *
     * @return bool
     */
    public function verifyIfOrderStatus($orderStatusConfigBefore, $currentOrderStatus, $orderStatusConfigCancel)
    {
        if (!is_null($orderStatusConfigBefore)) {
            if ($orderStatusConfigBefore == $currentOrderStatus) {
                return true;
            }
        }

        if (!is_null($orderStatusConfigCancel)) {
            if ($orderStatusConfigCancel == $currentOrderStatus) {
                return true;
            }
        }

        return false;
    }
}
