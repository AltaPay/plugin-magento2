<?php
namespace SDM\Altapay\Model;

use Altapay\Api\Ecommerce\Callback;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Api\Test\TestAuthentication;
use Altapay\Exceptions\ClientException;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Exceptions\ResponseMessageException;
use Altapay\Request\Address;
use Altapay\Request\Config;
use Altapay\Request\Customer;
use Altapay\Request\OrderLine;
use Altapay\Response\CallbackResponse;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use SDM\Altapay\Model\ConstantConfig;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Framework\DB\TransactionFactory;
use SDM\Altapay\Helper\Data;
class Generator
{

    const MODULE_CODE = 'SDM_Altapay';
    /**
     * @var ModuleListInterface
     */
    private $moduleList;
        /**
     * @var Helper Data
     */
    private $helper;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var PaymentData
     */
    private $paymentData;

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
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Monolog
     */
    private $_logger;

     /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
     private $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    private $_transaction;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $_orderRepository;       
    /**
     * The ShipmentNotifier class is used to send a notification email to the customer.
     *
     * @var ShipmentNotifier
     */
    private $_shipmentNotifier;
    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $transactionFactory;

    public function __construct(
        Quote $quote,
        UrlInterface $urlInterface,
        PaymentData $paymentData,
        Session $checkoutSession,
        Http $request,
        Order $order,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        SystemConfig $systemConfig,
        Monolog $_logger,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        InvoiceService $invoiceService,
        Transaction $transaction,
        OrderRepositoryInterface $orderRepository,
        ShipmentNotifier $shipmentNotifier,
        TransactionFactory $transactionFactory,
        Data $helper
    ) {
        $this->quote = $quote;
        $this->urlInterface = $urlInterface;
        $this->paymentData = $paymentData;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->systemConfig = $systemConfig;
        $this->_logger = $_logger;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->_orderRepository = $orderRepository;
        $this->invoiceSender = $invoiceSender;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->transactionFactory = $transactionFactory;
        $this->helper = $helper;
    }

    /**
     * Generate parameters
     *
     * @param int $terminalId
     * @param string $orderId
     * @return array
     */
    public function createRequest($terminalId, $orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            $storeName = $order->getStore()->getName();
            $websiteNAme = $order->getStore()->getWebsite()->getName();
            //Test the conn with the Payment Gateway
            $auth = $this->systemConfig->getAuth($storeCode);
            $api = new TestAuthentication($auth);

            $response = $api->call();
            if (! $response) {
                $this->restoreOrderFromOrderId($order->getIncrementId());
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = __(ConstantConfig::AUTH_MESSAGE);
                return $requestParams;
            }
            //Transaction Info
            $transactionDetail = $this->helper->transactionDetail($orderId); 
                   
            $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', $storeScope, $storeCode);
            $request = new PaymentRequest($auth);
            $request
            ->setTerminal($terminalName)
            ->setShopOrderId($order->getIncrementId())
            ->setAmount((float) $order->getGrandTotal())
            ->setCurrency($order->getOrderCurrencyCode())
            ->setCustomerInfo($this->setCustomer($order))
            ->setConfig($this->setConfig())
            ->setTransactionInfo($transactionDetail);
            

            if ($fraud = $this->systemConfig->getTerminalConfig($terminalId, 'fraud', $storeScope, $storeCode)) {
                $request->setFraudService($fraud);
            }

            if ($lang = $this->systemConfig->getTerminalConfig($terminalId, 'language', $storeScope, $storeCode)) {
                $langArr = explode('_', $lang, 2);
                if (isset($langArr[0])) {
                    $language = $langArr[0];
                    $request->setLanguage($language);
                }
            }

            $autoCaptureEnable =  $this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode);
            if ($autoCaptureEnable) {
                $request->setType('paymentAndCapture');
            }
            $orderlines = [];
            $sendShipment = false;
            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                $product_type = $item->getProductType();
                $productOriginalPriceWithOrWithoutTax = $item->getBaseOriginalPrice();
                $productSpecialPrice = $item->getPrice();
                $orderline = new OrderLine(
                    $item->getName(),
                    $item->getSku(),
                    $item->getQtyOrdered(),
                    $productOriginalPriceWithOrWithoutTax
                );

                if($product_type != 'virtual' && $product_type != 'downloadable'){
                    $sendShipment = true;
                }
                $orderline->setGoodsType('item');
                //in case of cart rule discount, send tax after discount
                $orderline->taxAmount = $item->getTaxAmount();
                
                if($productSpecialPrice != null && $productOriginalPriceWithOrWithoutTax > $productSpecialPrice && empty($order->getDiscountDescription())){
                    $orderline->discount = (($productOriginalPriceWithOrWithoutTax-$productSpecialPrice)/$productOriginalPriceWithOrWithoutTax)*100;
                    //In case of catalog rule discount, send tax before discount
                    $taxBeforeDiscount = ($productOriginalPriceWithOrWithoutTax * $item->getTaxPercent())/100;
                    $orderline->taxAmount = $taxBeforeDiscount * $item->getQtyOrdered();
                }
                $orderlines[] = $orderline;
            }
            if (abs($order->getDiscountAmount()) > 0) {
                // Handling price reductions
                $orderline = new OrderLine(
                    $order->getDiscountDescription(),
                    'discount',
                    1,
                    $order->getDiscountAmount()
                );
                $orderline->setGoodsType('handling');
                $orderlines[] = $orderline;
            }
            if($sendShipment){
             $shippingAddress = $order->getShippingMethod(true);
             $method = isset($shippingAddress['method']) ? $shippingAddress['method'] : '';
             $carrier_code = isset($shippingAddress['carrier_code']) ? $shippingAddress['carrier_code'] : '';
             if(!empty($shippingAddress)){
               $orderlines[] = (new OrderLine(
                $method,
                $carrier_code,
                1,
                $order->getShippingInclTax()
            ))->setGoodsType('shipment');  
           }
       }          
       $request->setOrderLines($orderlines);
       try {
        /** @var \Altapay\Response\PaymentRequestResponse $response */
        $response = $request->call();
        $requestParams['result'] = __(ConstantConfig::SUCCESS);
        $requestParams['formurl'] = $response->Url;
                // set before payment status
        $orderStatusBefore = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
        if ($orderStatusBefore) {
          $this->setCustomOrderStatus($order, Order::STATE_NEW, 'before');
      }
                // set notification
      $order->addStatusHistoryComment(__(ConstantConfig::REDIRECT_TO_VALITOR) . $response->PaymentRequestId);
      $extensionAttribute = $order->getExtensionAttributes();
      if ($extensionAttribute && $extensionAttribute->getValitorPaymentFormUrl()) {
        $extensionAttribute->setValitorPaymentFormUrl($response->Url);
    }

    $order->setValitorPaymentFormUrl($response->Url);

    $order->getResource()->save($order);

                //set check when user redirect
    $this->checkoutSession->setValitorCustomerRedirect(true);

    return $requestParams;
} catch (ClientException $e) {
    $requestParams['result'] = __(ConstantConfig::ERROR);
    $requestParams['message'] = $e->getResponse()->getBody();
} catch (ResponseHeaderException $e) {
    $requestParams['result'] = __(ConstantConfig::ERROR);
    $requestParams['message'] = $e->getHeader()->ErrorMessage;
} catch (ResponseMessageException $e) {
    $requestParams['result'] = __(ConstantConfig::ERROR);
    $requestParams['message'] = $e->getMessage();
} catch (\Exception $e) {
    $requestParams['result'] = __(ConstantConfig::ERROR);
    $requestParams['message'] = $e->getMessage();
}

$this->restoreOrderFromOrderId($order->getIncrementId());
return $requestParams;
}

$this->restoreOrderFromOrderId($order->getIncrementId());
$requestParams['result']  = __(ConstantConfig::ERROR);
$requestParams['message'] = __(ConstantConfig::ERROR_MESSAGE);
return $requestParams;
}

    /**
     * @param $orderId
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function restoreOrderFromOrderId($orderId)
    {
        $order = $this->loadOrderFromOrderId($orderId);
        if ($order->getId()) {
            $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
            $quote
            ->setIsActive(1)
            ->setReservedOrderId(null)
            ;
            $quote->getResource()->save($quote);
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    public function createInvoice($order){

         if (!$order->getInvoiceCollection()->count()) {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();


        }  

    }
    /**
     * @param RequestInterface $request
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
                    $quote
                    ->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save()
                    ;
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
     */
    public function handleCancelStatusAction(RequestInterface $request,$responseStatus)
    {
        $stateWhenRedirectCancel = Order::STATE_CANCELED;
        $statusWhenRedirectCancel = Order::STATE_CANCELED;
        $responseComment = __(ConstantConfig::CONSUMER_CANCEL_PAYMENT);
        if($responseStatus != 'cancelled'){
            $responseComment = __(ConstantConfig::UNKNOWN_PAYMENT_STATUS_MERCHANT);	
        }
        $historyComment = __(ConstantConfig::CANCELLED).'|'.$responseComment;
        //TODO: fetch the MerchantErrorMessage and use it as historyComment
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
        }

        $storeCode = $order->getStore()->getCode();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

        if ($orderStatusCancel) {
            $statusWhenRedirectCancel = $orderStatusCancel;
        }
        $this->handleOrderStateAction($request, $stateWhenRedirectCancel, $statusWhenRedirectCancel, $historyComment);
    }
    
         /**
     * @param RequestInterface $request
     */
         public function handleFailedStatusAction(RequestInterface $request, $msg, $merchantErrorMsg, $responseStatus)
         {
            $historyComment = $responseStatus.'|'.$msg;
            if(!is_null($merchantErrorMsg)){
               $historyComment = $responseStatus.'|'.$msg.'|'.$merchantErrorMsg;
           }
           $transInfo = null;
           $callback = new Callback($request->getPostValue());
           $response = $callback->call();
           if ($response) {
            $order = $this->loadOrderFromCallback($response);
            $transInfo = sprintf(
                "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
                $response->transactionId,
                $response->paymentId,
                $response->creditCardToken
            );
        }
        
        //check if order status set oin configuaration
        $stateWhenRedirectFail = Order::STATE_CANCELED;
        $statusWhenRedirectFail = Order::STATE_CANCELED;
        $storeCode = $order->getStore()->getCode();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $orderStatusCancel = $this->systemConfig->getStatusConfig('cancel', $storeScope, $storeCode);

        if ($orderStatusCancel) {
            $statusWhenRedirectFail = $orderStatusCancel;
        }

        $this->handleOrderStateAction(
            $request,
            $stateWhenRedirectFail,
            $statusWhenRedirectFail,
            $historyComment,
            $transInfo
        );
    }

    /**
     * @param RequestInterface $request
     * @param string $orderState
     * @param string $orderStatus
     * @param string $historyComment
     * @param null $transactionInfo
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
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
            $order->setState($orderState);
            $order->setIsNotified(false);
            if (!is_null($transactionInfo)) {
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
     * @param $comment
     * @param RequestInterface $request
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function completeCheckout($comment, RequestInterface $request)
    {
        $callback = new Callback($request->getParams());
        $response = $callback->call();
        if ($response) {
            $order = $this->loadOrderFromCallback($response);
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            if ($order->getId()) {
                // @todo Write data to DB
                $payment = $order->getPayment();
                $payment->setPaymentId($response->paymentId);
                $payment->setLastTransId($response->transactionId);
                $payment->setCcTransId($response->creditCardToken);
                $payment->save();
            }

            if (!$order->getEmailSent()) {
                $this->orderSender->send($order);
            }
            
            //unset redirect if success
            $this->checkoutSession->unsValitorCustomerRedirect();

            $isCaptured = false;
            foreach (SystemConfig::getTerminalCodes() as $terminalName) {
                if ($this->systemConfig->getTerminalConfigFromTerminalName(
                    $terminalName,
                    'terminalname',
                    $storeScope,
                    $storeCode
                ) === $response->Transactions[0]->Terminal
            ) {
                    $isCaptured = $this->systemConfig->getTerminalConfigFromTerminalName($terminalName, 'capture', $storeScope, $storeCode);
                    break;
                }
            }
            $orderStatusAfterPayment = $this->systemConfig->getStatusConfig('process', $storeScope, $storeCode);
            $orderStatus_capture = $this->systemConfig->getStatusConfig('autocapture', $storeScope, $storeCode);

            if ($isCaptured) {
                if($orderStatus_capture == "complete"){
                    $this->setCustomOrderStatus($order, Order::STATE_COMPLETE, 'autocapture');
                    $order->addStatusHistoryComment(__(ConstantConfig::PAYMENT_COMPLETE));  
                }

                else{
                    $this->setCustomOrderStatus($order, Order::STATE_PROCESSING, 'process');
                }

            }else{
                if($orderStatusAfterPayment){
                   $this->setCustomOrderStatus($order, $orderStatusAfterPayment, 'process');

               } else {
                    $this->setCustomOrderStatus($order, Order::STATE_PROCESSING, 'process');
                }
            }

            $order->addStatusHistoryComment(
                sprintf(
                    "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
                    $response->transactionId,
                    $response->paymentId,
                    $response->creditCardToken
                )
            );
            $order->setIsNotified(false);
            $order->getResource()->save($order);
            // Create invoice if the type is Payment And Capture
            if($response->type == "paymentAndCapture" ){
                $this->createInvoice($order);
            }
        }
    }

    /**
     * @param CallbackResponse $response
     * @return Order
     */
    protected function loadOrderFromCallback(CallbackResponse $response)
    {
        return $this->loadOrderFromOrderId($response->shopOrderId);
    }

    /**
     * @param string $orderId
     * @return Order
     */
    protected function loadOrderFromOrderId($orderId)
    {
        $order = $this->order->loadByIncrementId($orderId);
        return $order;
    }

    /**
     * @param Order $order
     * @param $state
     * @param $statusKey
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    protected function setCustomOrderStatus(Order $order, $state, $statusKey)
    {
        $order->setState($state);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode = $order->getStore()->getCode();
        if ($status = $this->systemConfig->getStatusConfig($statusKey, $storeScope, $storeCode)) {
            $order->setStatus($status);
        }
        $order->getResource()->save($order);
    }

    /**
     * @return Config
     */
    protected function setConfig()
    {
        $config = new Config();
        $config->setCallbackOk($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_OK));
        $config->setCallbackFail($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_FAIL));
        $config->setCallbackRedirect($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_REDIRECT));
        $config->setCallbackOpen($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_OPEN));
        $config->setCallbackNotification($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_NOTIFICATION));
        //$config->setCallbackVerifyOrder($this->urlInterface->getDirectUrl(ConstantConfig::VERIFY_ORDER));
        $config->setCallbackForm($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_CALLBACK));
        return $config;
    }

    /**
     * @param Order $order
     * @return Customer
     */
    protected function setCustomer(Order $order)
    {
        $billingAddress = new Address();
        if ($order->getBillingAddress()) {
            $address = $order->getBillingAddress()->convertToArray();
            $billingAddress->Email = $order->getBillingAddress()->getEmail();
            $billingAddress->Firstname = $address['firstname'];
            $billingAddress->Lastname = $address['lastname'];
            $billingAddress->Address = $address['street'];
            $billingAddress->City = $address['city'];
            $billingAddress->PostalCode = $address['postcode'];
            $billingAddress->Region = $address['region'] ?: '0';
            $billingAddress->Country = $address['country_id'];
        }
        $customer = new Customer($billingAddress);

        if ($order->getShippingAddress()) {
         $address = $order->getShippingAddress()->convertToArray();
         $shippingAddress = new Address();
         $shippingAddress->Email = $order->getShippingAddress()->getEmail();
         $shippingAddress->Firstname = $address['firstname'];
         $shippingAddress->Lastname = $address['lastname'];
         $shippingAddress->Address = $address['street'];
         $shippingAddress->City = $address['city'];
         $shippingAddress->PostalCode = $address['postcode'];
         $shippingAddress->Region = $address['region'] ?: '0';
         $shippingAddress->Country = $address['country_id'];
         $customer->setShipping($shippingAddress);
     }
     else{
        $customer->setShipping($billingAddress);
    }

    if ($order->getBillingAddress()) {
        $customer->setEmail($order->getBillingAddress()->getEmail());
        $customer->setPhone($order->getBillingAddress()->getTelephone());
    } elseif ($order->getShippingAddress()) {
        $customer->setEmail($order->getShippingAddress()->getEmail());
        $customer->setPhone($order->getShippingAddress()->getTelephone());
    }
    else {
        $customer->setEmail($order->getBillingAddress()->getEmail());
        $customer->setPhone($order->getBillingAddress()->getTelephone());
    } 

    return $customer;
}

public function getCheckoutSession()
{
    return $this->checkoutSession;
}
}
