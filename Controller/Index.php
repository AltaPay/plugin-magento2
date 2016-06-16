<?php
namespace SDM\Altapay\Controller;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use SDM\Altapay\Model\Generator;

abstract class Index extends Action
{

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Generator
     */
    protected $generator;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    public function __construct(Context $context, PageFactory $pageFactory, Order $order, Quote $quote, Session $checkoutSession, Generator $generator, LoggerInterface $logger)
    {
        parent::__construct($context);
        $this->order = $order;
        $this->quote = $quote;
        $this->checkoutSession = $checkoutSession;
        $this->generator = $generator;
        $this->logger = $logger;
        $this->pageFactory = $pageFactory;
    }

    public function checkPost()
    {
        return $this->getRequest()->isPost();
    }

    protected function writeLog()
    {
        $calledClass = get_called_class();
        $this->logger->debug('- BEGIN: ' . $calledClass);
        if (method_exists($this->getRequest(), 'getPostValue')) {
            $this->logger->debug('-- PostValue --');
            $this->logger->debug(print_r($this->getRequest()->getPostValue(), true));
        }
        $this->logger->debug('-- Params --');
        $this->logger->debug(print_r($this->getRequest()->getParams(), true));
        $this->logger->debug('- END: ' . $calledClass);
    }

}
