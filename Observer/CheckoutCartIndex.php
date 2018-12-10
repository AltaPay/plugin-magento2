<?php
namespace SDM\Altapay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage as CouponUsage;

class CheckoutCartIndex implements ObserverInterface
{

    const CHECK_ORDER_STATUS_BEFORE_CANCEL = 'pending'; 

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
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        Coupon $coupon,
        CouponUsage $couponUsage
    ) {
        $this->session = $session;
        $this->quoteFactory = $quoteFactory;
        $this->messageManager = $messageManager;
        $this->orderFactory   = $orderFactory;
        $this->coupon          = $coupon;
        $this->couponUsage     = $couponUsage;
    }


    /**
     * @param Observer $observer
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //session set in generator create request
        if ($this->session->getAltapayCustomerRedirect()) {

          $order = $this->session->getLastRealOrder();
          $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());

          //if quote id exist and order status is pending
          if ($quote->getId() && $order->getStatus() == self::CHECK_ORDER_STATUS_BEFORE_CANCEL) {
            //get quote Id from order and set as active
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $this->session->replaceQuote($quote)->unsLastRealOrderId();
            //set order status and comments
            $historyComment = 'Payment failed! Consumer has pressed the back button from the payment page.';
            $order->setState(Order::STATE_CANCELED);
            $order->setIsNotified(false);
            $order->addStatusHistoryComment($historyComment, \Magento\Sales\Model\Order::STATE_CANCELED);

            //if coupon applied revert it
            if ($order->getCouponCode()) {
              $this->resetCouponAfterCancellation($order);
            }

            $order->getResource()->save($order);
            //show fail message
            $this->messageManager->addErrorMessage('Payment failed due to the browser back button usage!');
           }
            $this->session->unsAltapayCustomerRedirect();
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
}



