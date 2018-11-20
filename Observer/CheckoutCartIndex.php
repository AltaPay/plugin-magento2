<?php
namespace SDM\Altapay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Model\SystemConfig;

class CheckoutCartIndex implements ObserverInterface
{

    /** @var \Magento\Checkout\Model\Session */
    private $session;

    /** @var \Magento\Quote\Model\QuoteFactory */
    private $quoteFactory;
    protected $_messageManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $session,
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
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $this->session->replaceQuote($quote)->unsLastRealOrderId();
            $this->messageManager->addErrorMessage('Payment Failed.');
        }
    }
}
