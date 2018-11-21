<?php
namespace SDM\Altapay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartIndex implements ObserverInterface
{

    /** @var \Magento\Checkout\Model\Session */
    private $session;

    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;
     
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        $this->session = $session;
        $this->quoteFactory = $quoteFactory;
        $this->messageManager = $messageManager;
    }


    /**
     * @param Observer $observer
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $this->session->getLastRealOrder();
        $quote = $this->quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
        if ($quote->getId()) {
            //get quote Id from order and set as active
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $this->session->replaceQuote($quote)->unsLastRealOrderId();

            //set order status and comments
            $historyComment = 'Payment failed! Consumer has pressed the back button from the payment page.';

            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->setIsNotified(false);
            $order->addStatusHistoryComment($historyComment, \Magento\Sales\Model\Order::STATE_CANCELED);
            $order->getResource()->save($order);
            //show fail message
            $this->messageManager->addErrorMessage('Payment failed due to the browser back button usage!');
        }
    }
}
