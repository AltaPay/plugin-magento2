<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model;

use Altapay\Api\Ecommerce\Callback;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Api\Test\TestAuthentication;
use Altapay\Exceptions\ClientException;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Exceptions\ResponseMessageException;
use Altapay\Request\Config;
use Altapay\Response\CallbackResponse;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use SDM\Altapay\Model\Handler\CustomerHandler;
use SDM\Altapay\Model\Handler\PriceHandler;
use SDM\Altapay\Model\Handler\DiscountHandler;
use SDM\Altapay\Model\Handler\CreatePaymentHandler;
use SDM\Altapay\Model\TokenFactory;
use SDM\Altapay\Model\ApplePayOrder;
use Magento\Sales\Model\OrderFactory;
use Altapay\Response\PaymentRequestResponse;
use Altapay\Api\Payments\CardWalletAuthorize;
use Magento\Payment\Model\MethodInterface;
use Magento\Checkout\Model\Cart;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

/**
 * Class Generator
 * Handle the create payment related functionality.
 */
class Generator
{
    /**
     * @var Helper Data
     */
    private $helper;
    /**
     * @var Quote
     */
    private $quote;
    /**
     * @var UrlInterface
     */
    private $urlInterface;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;
    /**
     * @var InvoiceService
     */
    private $invoiceService;
    /**
     * @var OrderLinesHandler
     */
    private $orderLines;
    /**
     * @var Helper Config
     */
    private $storeConfig;
    /**
     * @var CustomerHandler
     */
    private $customerHandler;
    /**
     * @var PriceHandler
     */
    private $priceHandler;
    /**
     * @var DiscountHandler
     */
    private $discountHandler;
    /**
     * @var CreatePaymentHandler
     */
    private $paymentHandler;
    /**
     * @var TokenFactory
     */
    private $dataToken;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var StockStateInterface
     */
    private $stockItem;
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;
    /**
     * @var Cart
     */
    private  $modelCart;

    /**
     *
     * @param Quote                $quote
     * @param UrlInterface         $urlInterface
     * @param Session              $checkoutSession
     * @param Http                 $request
     * @param Order                $order
     * @param OrderSender          $orderSender
     * @param SystemConfig         $systemConfig
     * @param OrderFactory         $orderFactory
     * @param InvoiceService       $invoiceService
     * @param TransactionFactory   $transactionFactory
     * @param Data                 $helper
     * @param storeConfig          $storeConfig
     * @param OrderLinesHandler    $orderLines
     * @param CustomerHandler      $customerHandler
     * @param PriceHandler         $priceHandler
     * @param DiscountHandler      $discountHandler
     * @param CreatePaymentHandler $paymentHandler
     * @param TokenFactory         $dataToken
     * @param StockStateInterface            $stockItem
     * @param StockRegistryInterface         $stockRegistry
     * @param Cart                           $modelCart
     */
    public function __construct(
        Quote $quote,
        UrlInterface $urlInterface,
        Session $checkoutSession,
        Http $request,
        Order $order,
        OrderSender $orderSender,
        SystemConfig $systemConfig,
        OrderFactory $orderFactory,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        Data $helper,
        storeConfig $storeConfig,
        OrderLinesHandler $orderLines,
        CustomerHandler $customerHandler,
        PriceHandler $priceHandler,
        DiscountHandler $discountHandler,
        CreatePaymentHandler $paymentHandler,
        TokenFactory $dataToken,
        StockStateInterface $stockItem,
        StockRegistryInterface $stockRegistry,
        Cart $modelCart,
        ApplePayOrder $applePayOrder
    ) {
        $this->quote              = $quote;
        $this->urlInterface       = $urlInterface;
        $this->checkoutSession    = $checkoutSession;
        $this->request            = $request;
        $this->order              = $order;
        $this->orderSender        = $orderSender;
        $this->systemConfig       = $systemConfig;
        $this->invoiceService     = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->orderFactory       = $orderFactory;
        $this->helper             = $helper;
        $this->storeConfig        = $storeConfig;
        $this->orderLines         = $orderLines;
        $this->customerHandler    = $customerHandler;
        $this->priceHandler       = $priceHandler;
        $this->discountHandler    = $discountHandler;
        $this->paymentHandler     = $paymentHandler;
        $this->dataToken          = $dataToken;
        $this->stockItem          = $stockItem;
        $this->stockRegistry      = $stockRegistry;
        $this->modelCart          = $modelCart;
        $this->applePayOrder      = $applePayOrder;
    }

    /**
     * createRequest to altapay
     *
     * @param int    $terminalId
     * @param string $orderId
     *
     * @return array
     */
    public function createRequest($terminalId, $orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $couponCode       = $order->getDiscountDescription();
            $couponCodeAmount = $order->getDiscountAmount();
            $discountAllItems = $this->discountHandler->allItemsHaveDiscount($order->getAllItems());
            $orderLines       = $this->itemOrderLines($couponCodeAmount, $order, $discountAllItems);
            if ($this->orderLines->sendShipment($order) && !empty($order->getShippingMethod(true))) {
                $orderLines[] = $this->orderLines->handleShipping($order, $discountAllItems, true);
            }
            if ($discountAllItems && abs($couponCodeAmount) > 0) {
                $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
            }
            if (!empty($this->fixedProductTax($order))) {
                $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($order));
            }
            $request = $this->preparePaymentRequest($order, $orderLines, $orderId, $terminalId, null);
            if ($request) {
                return $this->sendPaymentRequest($order, $request);
            }
        }

        return $this->restoreOrderAndReturnError($order);
    }

    /**
     * @param $orderId
     *
     * @throws AlreadyExistsException
     */
    public function restoreOrderFromOrderId($orderId)
    {
        $order = $this->loadOrderFromCallback($orderId);
        if ($order->getId()) {
            $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
            $quote->setIsActive(1)->setReservedOrderId(null);
            $quote->getResource()->save($quote);
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    /**
     * @param      $order
     * @param bool $requireCapture
     */
    public function createInvoice($order, $requireCapture = false)
    {
        if (filter_var($requireCapture, FILTER_VALIDATE_BOOLEAN) === true) {
            $captureType = Invoice::CAPTURE_ONLINE;
        } else {
            $captureType = Invoice::CAPTURE_OFFLINE;
        }

        if (!$order->getInvoiceCollection()->count()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase($captureType);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $transaction = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
            $transaction->save();
        }
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool
     * @throws \Exception
     */
    public function restoreOrderFromRequest(RequestInterface $request)
    {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            if ($order->getQuoteId()) {
                if ($quote = $this->quote->loadByIdWithoutStore($order->getQuoteId())) {
                    $quote->setIsActive(1)->setReservedOrderId(null)->save();
                    $this->checkoutSession->replaceQuote($quote);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param RequestInterface $request
     */
    public function handleNotificationAction(RequestInterface $request)
    {
        $this->completeCheckout(__(ConstantConfig::NOTIFICATION_CALLBACK), $request);
    }

    /**
     * @param RequestInterface $request
     * @param                  $responseStatus
     */
    public function handleCancelStatusAction(RequestInterface $request, $responseStatus)
    {
        $responseComment = __(ConstantConfig::CONSUMER_CANCEL_PAYMENT);
        if ($responseStatus != 'cancelled') {
            $responseComment = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_MERCHANT);
        }
        $historyComment = __(ConstantConfig::CANCELLED) . '|' . $responseComment;
        //TODO: fetch the MerchantErrorMessage and use it as historyComment
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            //check if order status set in configuration
            $statusKey         = Order::STATE_CANCELED;
            $storeCode         = $order->getStore()->getCode();
            $storeScope        = ScopeInterface::SCOPE_STORE;
            $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

            if ($orderStatusCancel) {
                $statusKey = $orderStatusCancel;
            }
            $this->handleOrderStateAction($request, Order::STATE_CANCELED, $statusKey, $historyComment);
        }
    }

    /**
     * @param RequestInterface $request
     * @param                  $msg
     * @param                  $merchantErrorMsg
     * @param                  $responseStatus
     *
     * @throws \Exception
     */
    public function handleFailedStatusAction(RequestInterface $request, $msg, $merchantErrorMsg, $responseStatus)
    {
        $historyComment = $responseStatus . '|' . $msg;
        if (!empty($merchantErrorMsg)) {
            $historyComment = $historyComment . '|' . $merchantErrorMsg;
        }
        $transInfo = null;
        $callback  = new Callback($request->getPostValue());
        $response  = $callback->call();
        if ($response) {
            $order     = $this->loadOrderFromCallback($response);
            $transInfo = $this->getTransactionInfoFromResponse($response);
            //check if order status set in configuration
            $statusKey         = Order::STATE_CANCELED;
            $storeCode         = $order->getStore()->getCode();
            $storeScope        = ScopeInterface::SCOPE_STORE;
            $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

            if ($orderStatusCancel) {
                $statusKey = $orderStatusCancel;
            }
            $this->handleOrderStateAction($request, Order::STATE_CANCELED, $statusKey, $historyComment, $transInfo);
        }
    }

    /**
     * @param CallbackResponse $response
     *
     * @return Order
     */
    private function loadOrderFromCallback(CallbackResponse $response)
    {
        return $this->orderFactory->create()->loadByIncrementId($response->shopOrderId);
    }

    /**
     * @param RequestInterface $request
     * @param string           $orderState
     * @param string           $orderStatus
     * @param string           $historyComment
     * @param null             $transactionInfo
     *
     * @return bool
     * @throws AlreadyExistsException
     */
    public function handleOrderStateAction(
        RequestInterface $request,
        $orderState = Order::STATE_NEW,
        $orderStatus = Order::STATE_NEW,
        $historyComment = "Order state changed",
        $transactionInfo = null
    ) {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            if ($orderStatus === 'canceled') {
                $order->cancel();
            }
            $order->setState($orderState);
            $order->setIsNotified(false);
            if ($transactionInfo !== null) {
                $order->addStatusHistoryComment($transactionInfo);
            }
            $order->addStatusHistoryComment($historyComment, $orderStatus);
            $order->getResource()->save($order);

            return true;
        }

        return false;
    }

    /**
     * @param RequestInterface $request
     */
    public function handleOkAction(RequestInterface $request)
    {
        $this->completeCheckout(__(ConstantConfig::OK_CALLBACK), $request);
    }

    /**
     * @param                  $comment
     * @param RequestInterface $request
     *
     * @throws \Exception
     */
    private function completeCheckout($comment, RequestInterface $request)
    {
        $callback       = new Callback($request->getParams());
        $response       = $callback->call();
        $paymentType    = $response->type;
        $requireCapture = $response->requireCapture;
        $paymentStatus  = strtolower($response->paymentStatus);
        $responseStatus = $response->status;
        $max_date = '';
        $latestTransKey = '';

        if ($paymentStatus === 'released') {
            $this->handleCancelStatusAction($request, $responseStatus);
            return;
        }

        if ($response) {
            $order      = $this->loadOrderFromCallback($response);
            $storeScope = ScopeInterface::SCOPE_STORE;
            $storeCode  = $order->getStore()->getCode();
            foreach ($response->Transactions as $key=>$value) {
                if ($value->CreatedDate > $max_date) {
                    $max_date = $value->CreatedDate;
                    $latestTransKey = $key;
                }
            }
            if ($order->getId()) {
                $cardType = '';
                $expires  = '';
                //Update stock quantity
                if($order->getState() == 'canceled') {
                    $this->updateStockQty($order);
                }
                $this->resetCanceledQty($order);
                if (isset($response->Transactions[$latestTransKey])) {
                    $transaction = $response->Transactions[$latestTransKey];
                    if (isset($transaction->CreditCardExpiry->Month) && isset($transaction->CreditCardExpiry->Year)) {
                        $expires = $transaction->CreditCardExpiry->Month . '/' . $transaction->CreditCardExpiry->Year;
                    }
                    if (isset($transaction->PaymentSchemeName)) {
                        $cardType = $transaction->PaymentSchemeName;
                    }
                }
                $payment = $order->getPayment();
                $payment->setPaymentId($response->paymentId);
                $payment->setLastTransId($response->transactionId);
                $payment->setCcTransId($response->creditCardToken);
                $payment->setAdditionalInformation('cc_token', $response->creditCardToken);
                $payment->setAdditionalInformation('masked_credit_card', $response->maskedCreditCard);
                $payment->setAdditionalInformation('expires', $expires);
                $payment->setAdditionalInformation('card_type', $cardType);
                $payment->setAdditionalInformation('payment_type', $paymentType);
                $payment->save();
                //send order confirmation email
                $this->sendOrderConfirmationEmail($comment, $order);
                //unset redirect if success
                $this->checkoutSession->unsAltapayCustomerRedirect();

                $orderStatusAfterPayment = $this->systemConfig->getStatusConfig('process', $storeScope, $storeCode);
                $orderStatusCapture      = $this->systemConfig->getStatusConfig('autocapture', $storeScope, $storeCode);
                $setOrderStatus          = true;
                $orderState              = Order::STATE_PROCESSING;
                $statusKey               = 'process';

                if ($this->isCaptured($response, $storeCode, $storeScope, $latestTransKey)) {
                    if ($orderStatusCapture == "complete") {
                        if ($this->orderLines->sendShipment($order)) {
                            $orderState = Order::STATE_COMPLETE;
                            $statusKey  = 'autocapture';
                            $order->addStatusHistoryComment(__(ConstantConfig::PAYMENT_COMPLETE));
                        } else {
                            $setOrderStatus = false;
                            $order->addStatusToHistory($orderStatusCapture, ConstantConfig::PAYMENT_COMPLETE, false);
                        }
                    }
                } else {
                    if ($orderStatusAfterPayment) {
                        $orderState = $orderStatusAfterPayment;
                    }
                }
                if ($setOrderStatus) {
                    $this->paymentHandler->setCustomOrderStatus($order, $orderState, $statusKey);
                }
                $order->addStatusHistoryComment($comment);
                $order->addStatusHistoryComment($this->getTransactionInfoFromResponse($response));
                $order->setIsNotified(false);
 
                $order->getResource()->save($order);

                if (strtolower($paymentType) === 'paymentandcapture' || strtolower($paymentType) === 'subscriptionandcharge') {
                    $this->createInvoice($order, $requireCapture);
                }
            }
        }
    }

    /**
     * @param $order
     * return void
     */
    public function updateStockQty($order)
    {
        $cart = $this->modelCart;
        $quoteItems = $this->checkoutSession->getQuote()->getItemsCollection();
        foreach ($order->getAllItems() as $item) {
            $stockQty  = $this->stockItem->getStockQty($item->getProductId(), $item->getStore()->getWebsiteId());
            $qty       = $stockQty - $item->getQtyOrdered();
            $stockItem = $this->stockRegistry->getStockItemBySku($item['sku']);         
            $stockItem->setQty($qty);
            $stockItem->setIsInStock((bool)$qty); 
            $this->stockRegistry->updateStockItemBySku($item['sku'], $stockItem);
        }
        foreach($quoteItems as $item)
        {
            $cart->removeItem($item->getId())->save(); 
        }
    }
    
    /**
     * @param $response
     *
     * @return string
     */
    private function getTransactionInfoFromResponse($response)
    {
        return sprintf(
            "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
            $response->transactionId,
            $response->paymentId,
            $response->creditCardToken
        );
    }

    /**
     * @return Config
     */
    private function setConfig()
    {
        $config = new Config();
        $config->setCallbackOk($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_OK));
        $config->setCallbackFail($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_FAIL));
        $config->setCallbackRedirect($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_REDIRECT));
        $config->setCallbackOpen($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_OPEN));
        $config->setCallbackNotification($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_NOTIFICATION));
        $config->setCallbackForm($this->urlInterface->getDirectUrl(ConstantConfig::ALTAPAY_CALLBACK));

        return $config;
    }

    public function getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * @param Order $order
     *
     * @return float|int
     */
    public function fixedProductTax($order)
    {

        $weeTaxAmount = 0;
        foreach ($order->getAllItems() as $item) {
            $weeTaxAmount +=  $item->getWeeeTaxAppliedRowAmount();
        }

        return $weeTaxAmount;
    }


    /**
     * @param $couponCodeAmount
     * @param $order
     * @param $discountAllItems
     *
     * @return array
     */
    private function itemOrderLines($couponCodeAmount, $order, $discountAllItems)
    {
        $orderLines       = [];
        $storePriceIncTax = $this->storeConfig->storePriceIncTax();

        foreach ($order->getAllItems() as $item) {
            $productType          = $item->getProductType();
            $productOriginalPrice = $item->getBaseOriginalPrice();
            $taxPercent           = $item->getTaxPercent();
            $discountAmount       = $item->getBaseDiscountAmount();
            $parentItemType       = "";

            if ($item->getParentItem()) {
                $parentItemType = $item->getParentItem()->getProductType();
            }
            if ($productType != "bundle" && $parentItemType != "configurable") {
                if ($productOriginalPrice == 0) {
                    $productOriginalPrice = $item->getPriceInclTax();
                }
                if ($storePriceIncTax) {
                    $unitPriceWithoutTax = $this->priceHandler->getPriceWithoutTax($productOriginalPrice, $taxPercent);
                    $unitPrice           = bcdiv($unitPriceWithoutTax, 1, 2);
                } else {
                    $unitPrice           = $productOriginalPrice;
                }
                $dataForPrice         = $this->priceHandler->dataForPrice(
                    $item,
                    $unitPrice,
                    $couponCodeAmount,
                    $this->discountHandler->getItemDiscount($discountAmount, $originalPrice, $item->getQtyOrdered()),
                    $discountAllItems
                );
                $taxAmount            = $dataForPrice["taxAmount"];
                $catalogDiscount      = $dataForPrice["catalogDiscount"];
                $discount             = $this->discountHandler->orderLineDiscount(
                    $discountAllItems,
                    $dataForPrice["discount"],
                    $catalogDiscount
                );
                $itemTaxAmount        = $taxAmount;
                $orderLines[]         = $this->orderLines->itemOrderLine(
                    $item,
                    $unitPrice,
                    $discount,
                    $itemTaxAmount,
                    $order,
                    true,
                    $discountAllItems
                );
                $roundingCompensation = $this->priceHandler->compensationAmountCal(
                    $item,
                    $unitPrice,
                    $taxAmount,
                    $discount,
                    true
                );
                // check if rounding compensation amount, send in the separate orderline
                if ($roundingCompensation > 0 || $roundingCompensation < 0) {
                    $orderLines[] = $this->orderLines->compensationOrderLine(
                        "Compensation Amount",
                        "comp-" . $item->getItemId(),
                        $roundingCompensation
                    );
                }
            }
        }

        return $orderLines;
    }

    /**
     * @param $order
     *
     * @return mixed
     */
    private function restoreOrderAndReturnError($order)
    {
        $this->restoreOrderFromOrderId($order->getIncrementId());
        $requestParams['result']  = ConstantConfig::ERROR;
        $requestParams['message'] = __(ConstantConfig::ERROR_MESSAGE);

        return $requestParams;
    }

    /**
     * Prepare request to the altapay, sets the necessary parameters.
     *
     * @param $order
     * @param $orderLines
     * @param $orderId
     * @param $terminalId
     *
     * @return bool|PaymentRequest|CardWalletAuthorize
     */
    private function preparePaymentRequest($order, $orderLines, $orderId, $terminalId, $providerData)
    {
        $storeScope = $this->storeConfig->getStoreScope();
        $storeCode  = $order->getStore()->getCode();
        //Test the conn with the Payment Gateway
        $auth     = $this->systemConfig->getAuth($storeCode);
        $api      = new TestAuthentication($auth);
        $response = $api->call();
        if (!$response) {
            return false;
        }
        $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', $storeScope, $storeCode);
        $isApplePay = $this->systemConfig->getTerminalConfig($terminalId, 'isapplepay', $storeScope, $storeCode);
        //Transaction Info
        $transactionDetail = $this->helper->transactionDetail($orderId);
        $request           = new PaymentRequest($auth);
        if ($isApplePay) {
            $request = new CardWalletAuthorize($auth);
            $request->setProviderData($providerData);
        }
        $request->setTerminal($terminalName)
                ->setShopOrderId($order->getIncrementId())
                ->setAmount((float)number_format($order->getGrandTotal(), 2, '.', ''))
                ->setCurrency($order->getOrderCurrencyCode())
                ->setCustomerInfo($this->customerHandler->setCustomer($order))
                ->setConfig($this->setConfig())
                ->setTransactionInfo($transactionDetail)
                ->setSalesTax((float)number_format($order->getTaxAmount(), 2, '.', ''))
                ->setCookie($this->request->getServer('HTTP_COOKIE'));

        $post = $this->request->getPostValue();

        if (isset($post['tokenid'])) {
            $model      = $this->dataToken->create();
            $collection = $model->getCollection()->addFieldToFilter('id', $post['tokenid'])->getFirstItem();
            $data       = $collection->getData();
            if (!empty($data)) {
                $token = $data['token'];
                $request->setCcToken($token);
            }
        }

        if ($fraud = $this->systemConfig->getTerminalConfig($terminalId, 'fraud', $storeScope, $storeCode)) {
            $request->setFraudService($fraud);
        }

        if ($lang = $this->systemConfig->getTerminalConfig($terminalId, 'language', $storeScope, $storeCode)) {
            $langArr = explode('_', $lang, 2);
            if (isset($langArr[0])) {
                $request->setLanguage($langArr[0]);
            }
        }
        // check if auto capture enabled
        if ($this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode)) {
            $request->setType('paymentAndCapture');
        }
        //set orderlines to the request
        $request->setOrderLines($orderLines);

        return $request;
    }

    /**
     * Send payment request to the altapay.
     *
     * @param $order
     * @param $request
     *
     * @return mixed
     */
    private function sendPaymentRequest($order, $request)
    {
        $storeScope = $this->storeConfig->getStoreScope();
        $storeCode  = $order->getStore()->getCode();

        try {
            /** @var PaymentRequestResponse $response */
            $response                 = $request->call();
            $requestParams['result']  = ConstantConfig::SUCCESS;
            $requestParams['formurl'] = $response->Url;
            // set before payment status
            if ($this->systemConfig->getStatusConfig('before', $storeScope, $storeCode)) {
                $this->paymentHandler->setCustomOrderStatus($order, Order::STATE_NEW, 'before');
            }
            // set notification
            $order->addStatusHistoryComment(__(ConstantConfig::REDIRECT_TO_ALTAPAY) . $response->PaymentRequestId);
            $extensionAttribute = $order->getExtensionAttributes();
            if ($extensionAttribute && $extensionAttribute->getAltapayPaymentFormUrl()) {
                $extensionAttribute->setAltapayPaymentFormUrl($response->Url);
            }
            $order->setAltapayPaymentFormUrl($response->Url);
            $order->setAltapayPriceIncludesTax($this->storeConfig->storePriceIncTax());
            $order->getResource()->save($order);
            //set flag if customer redirect to Altapay
            $this->checkoutSession->setAltapayCustomerRedirect(true);

            return $requestParams;
        } catch (ClientException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getResponse()->getBody();
        } catch (ResponseHeaderException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getHeader()->ErrorMessage;
        } catch (ResponseMessageException $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getMessage();
        } catch (\Exception $e) {
            $requestParams['result']  = ConstantConfig::ERROR;
            $requestParams['message'] = $e->getMessage();
        }

        $this->restoreOrderFromOrderId($order->getIncrementId());

        return $requestParams;
    }

    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     *
     * @return bool|MethodInterface
     */
    private function isCaptured($response, $storeCode, $storeScope, $latestTransKey)
    {
        $isCaptured = false;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                $terminalName,
                'terminalname',
                $storeScope,
                $storeCode
            );
            if ($terminalConfig === $response->Transactions[$latestTransKey]->Terminal) {
                $isCaptured = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'capture',
                    $storeScope,
                    $storeCode
                );
                break;
            }
        }

        return $isCaptured;
    }

    /**
     * @param $comment
     * @param $order
     */
    private function sendOrderConfirmationEmail($comment, $order)
    {
        $currentStatus        = $order->getStatus();
        $orderHistories       = $order->getStatusHistories();
        $latestHistoryComment = array_pop($orderHistories);
        $prevStatus           = $latestHistoryComment->getStatus();

        $sendMail = true;
        if (strpos($comment, ConstantConfig::NOTIFICATION_CALLBACK) !== false && $currentStatus == $prevStatus) {
            $sendMail = false;
        }
        if (!$order->getEmailSent() && $sendMail == true) {
            $this->orderSender->send($order);
        }
    }

    /**
     * @param RequestInterface $request
     * @param                  $avsCode
     * @param                  $historyComment
     *
     * @return bool
     */
    public function avsCheck(RequestInterface $request, $avsCode, $historyComment)
    {
        $checkRejectionCase = false;
        $transInfo          = null;
        $callback           = new Callback($request->getPostValue());
        $response           = $callback->call();
        if ($response) {
            $order                 = $this->loadOrderFromCallback($response);
            $storeScope            = ScopeInterface::SCOPE_STORE;
            $storeCode             = $order->getStore()->getCode();
            $transInfo             = sprintf(
                "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
                $response->transactionId,
                $response->paymentId,
                $response->creditCardToken
            );
            $isAvsEnabled          = $this->checkAvsConfig($response, $storeCode, $storeScope, 'avscontrol');
            $isAvsEnforced         = $this->checkAvsConfig($response, $storeCode, $storeScope, 'enforceavs');
            $getAcceptedAvsResults = $this->getAcceptedAvsResults($response, $storeCode, $storeScope);

            if ($isAvsEnabled) {
                if ($isAvsEnforced && empty($avsCode)) {
                    $checkRejectionCase = true;
                } elseif (stripos($getAcceptedAvsResults, $avsCode) === false) {
                    $checkRejectionCase = true;
                }
            }
            if ($checkRejectionCase) {
                //check if order status set in configuration
                $statusKey         = Order::STATE_CANCELED;
                $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);
                //Save payment info in order to retrieve it for release operation
                if ($order->getId()) {
                    $this->savePaymentData($response, $order);
                }
                if ($orderStatusCancel) {
                    $statusKey = $orderStatusCancel;
                }
                $this->handleOrderStateAction($request, Order::STATE_CANCELED, $statusKey, $historyComment, $transInfo);
            }
        }

        return $checkRejectionCase;
    }

    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     * @param $configField
     *
     * @return bool
     */
    public function checkAvsConfig($response, $storeCode, $storeScope, $configField)
    {
        $isEnabled = false;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                $terminalName,
                'terminalname',
                $storeScope,
                $storeCode
            );
            if ($terminalConfig === $response->Transactions[$this->getLatestTransaction($response)]->Terminal) {
                $isEnabled = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    $configField,
                    $storeScope,
                    $storeCode
                );
                break;
            }
        }

        return $isEnabled;
    }

    /**
     * @param $response
     * @param $storeCode
     * @param $storeScope
     *
     * @return |null
     */
    public function getAcceptedAvsResults($response, $storeCode, $storeScope)
    {
        $acceptedAvsResults = null;
        foreach (SystemConfig::getTerminalCodes() as $terminalName) {
            $terminalConfig = $this->systemConfig->getTerminalConfigFromTerminalName(
                $terminalName,
                'terminalname',
                $storeScope,
                $storeCode
            );
            if ($terminalConfig === $response->Transactions[$this->getLatestTransaction($response)]->Terminal) {
                $acceptedAvsResults = $this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'avs_acceptance',
                    $storeScope,
                    $storeCode
                );
                break;
            }
        }

        return $acceptedAvsResults;
    }

    /**
     * @param $response
     * @param $order
     */
    public function savePaymentData($response, $order)
    {
        $payment = $order->getPayment();
        $payment->setPaymentId($response->paymentId);
        $payment->setLastTransId($response->transactionId);
        $payment->save();
    }

    /**
     * @param $order
     * 
     * @return void
     */
    public function resetCanceledQty($order) {
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyCanceled() > 0) {
                    $item->setQtyCanceled($item->getQtyToCancel());
                    $item->save();
            }
        }
    }

    public function getLatestTransaction($response) {
        $max_date = '';
        $latestTransKey = '';
        foreach ($response->Transactions as $key=>$value) {
            if ($value->CreatedDate > $max_date) {
                $max_date = $value->CreatedDate;
                $latestTransKey = $key;
            }
        }
        return $latestTransKey;
    }
    /**
     * @param $terminalId
     * @param $orderId
     * @param $providerData
     *
     * @return mixed
     */
    public function createRequestApplepay($terminalId, $orderId, $providerData)
    {
        $storeScope = $this->storeConfig->getStoreScope();
        $order = $this->order->load($orderId);
        $storeCode  = $order->getStore()->getCode();
        if ($order->getId()) {
            $couponCode       = $order->getDiscountDescription();
            $couponCodeAmount = $order->getDiscountAmount();
            $discountAllItems = $this->discountHandler->allItemsHaveDiscount($order->getAllItems());
            $orderLines       = $this->itemOrderLines($couponCodeAmount, $order, $discountAllItems);
            if ($this->orderLines->sendShipment($order) && !empty($order->getShippingMethod(true))) {
                $orderLines[] = $this->orderLines->handleShipping($order, $discountAllItems, true);
                //Shipping Discount Tax Compensation Amount
                $compAmount = $this->discountHandler->hiddenTaxDiscountCompensation($order, $discountAllItems, true);
                if ($compAmount > 0 && $discountAllItems == false) {
                    $orderLines[] = $this->orderLines->compensationOrderLine(
                        "Shipping compensation",
                        "comp-ship",
                        $compAmount
                    );
                }
            }
            if ($discountAllItems && abs($couponCodeAmount) > 0) {
                $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
            }
            if(!empty($this->fixedProductTax($order))){
                $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($order));
            }
            $request = $this->preparePaymentRequest($order, $orderLines, $orderId, $terminalId, $providerData);
            if ($request) {
                $response = $request->call();
                $this->applePayOrder->handleCardWalletPayment($response, $order);

                return $response;
            }
        }

        return $this->restoreOrderAndReturnError($order);
    }

}
