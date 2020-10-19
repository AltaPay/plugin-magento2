<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Block\Callback;

use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\ScopeInterface;

class Ordersummary extends Template
{
    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Config
     */
    protected $orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var Data $priceHelper
     */
    protected $priceHelper;
    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;

    /**
     * OrderSummary constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param OrderFactory                                     $orderFactory
     * @param Http                                             $request
     * @param Config                                           $orderConfig
     * @param \Magento\Framework\App\Http\Context              $httpContext
     * @param OrderRepositoryInterface                         $orderRepository
     * @param \Magento\Framework\App\Http\Context              $renderer
     * @param ProductRepository                                $productRepository
     * @param Data
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        OrderFactory $orderFactory,
        Http $request,
        Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        OrderRepositoryInterface $orderRepository,
        Renderer $renderer,
        ProductRepository $productRepository,
        Data $priceHelper,
        ScopeConfigInterface $appConfigScopeConfigInterface,
        array $data = []
    ) {

        parent::__construct($context, $data);
        $this->orderFactory                   = $orderFactory;
        $this->request                        = $request;
        $this->orderConfig                    = $orderConfig;
        $this->httpContext                    = $httpContext;
        $this->orderRepository                = $orderRepository;
        $this->renderer                       = $renderer;
        $this->productRepository              = $productRepository;
        $this->priceHelper                    = $priceHelper;
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
    }

    /**
     * Get orderif from param
     *
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->request->getParam('shop_orderid');
    }

    /**
     * Load order
     *
     * @return string
     */

    public function getOrder()
    {
        $orderIncrementId = $this->getOrderId();
        if ($orderIncrementId) {
            return $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        }

        return '';
    }

    /**
     * Format order address
     *
     * @return mixed
     */
    public function getFormattedAddress()
    {
        $order = $this->getOrder();
        if ($order->getShippingAddress()) {
            return $this->renderer->format($order->getShippingAddress(), 'html');
        } else {
            return $this->renderer->format($order->getBillingAddress(), 'html');
        }
    }

    /**
     * Get order payment title
     *
     * @return string
     */
    public function getPaymentMethodTitle()
    {
        $storeScope = ScopeInterface::SCOPE_STORE;
        $order      = $this->getOrder();
        $storeId    = $order->getStore()->getId();
        $payment    = $order->getPayment();
        $method     = $payment->getMethodInstance();
        $title      = $method->getConfigData('title', $storeId);
        $terminalID = $payment->getMethod();
        if ($title == null) {
            $terminalTitle = $this->_appConfigScopeConfigInterface
                ->getValue('payment/' . $terminalID . '/terminalname', $storeScope);
        } else {
            $terminalTitle = $title;
        }

        return $terminalTitle;
    }

    /**
     * Load product from productId
     *
     * @param $id
     *
     * @return mixed
     */
    public function getProductById($id)
    {
        return $this->productRepository->getById($id);
    }

    /**
     * Get Formated Price
     *
     * @param string $price
     *
     * @return mixed
     */
    public function getFormatedPrice($price = '')
    {
        return $this->priceHelper->currency($price, true, false);
    }
}
