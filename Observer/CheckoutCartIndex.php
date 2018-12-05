<?php
namespace SDM\Altapay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Exception\LocalizedException;

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
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->session = $session;
        $this->quoteFactory = $quoteFactory;
        $this->messageManager = $messageManager;
        $this->orderFactory   = $orderFactory;
    }


    /**
     * @param Observer $observer
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->session->getLastRealOrderId()) {
            try {
                $orderId = $this->session->getLastRealOrderId();
                $order = $orderId ? $this->orderFactory->create()->load($orderId) : false;
                if ($order->getAltapayPaymentFormUrl()) {
                    $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
                    //get quote Id from order and set as active
                    $quote->setIsActive(1)->setReservedOrderId(null)->save();
                    $this->session->replaceQuote($quote)->unsLastRealOrderId();

                     
                    $historyComment = 'Payment failed! Consumer has pressed the back button from the payment page.';

                    $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                    $order->setIsNotified(false);
                    $order->addStatusHistoryComment($historyComment, \Magento\Sales\Model\Order::STATE_CANCELED);
                    $order->getResource()->save($order);

                    $this->messageManager->addErrorMessage('Payment failed due to the browser back button usage!');
                }
            } catch (LocalizedException $e) {
                // catch and continue - do something when needed
            } catch (\Exception $e) {
                // catch and continue - do something when needed
            }

        }
    }
}


