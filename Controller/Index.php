<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * Index constructor.
     *
     * @param Context         $context
     * @param PageFactory     $pageFactory
     * @param Order           $order
     * @param Quote           $quote
     * @param Session         $checkoutSession
     * @param Generator       $generator
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Order $order,
        Quote $quote,
        Session $checkoutSession,
        Generator $generator,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->order           = $order;
        $this->quote           = $quote;
        $this->checkoutSession = $checkoutSession;
        $this->generator       = $generator;
        $this->logger          = $logger;
        $this->pageFactory     = $pageFactory;
    }

    /**
     * @return mixed
     */
    public function checkPost()
    {
        return $this->getRequest()->isPost();
    }

    /**
     * Write the logs to the Altapay logger.
     */
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
