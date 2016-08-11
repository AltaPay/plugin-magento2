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
use Altapay\Response\CallbackResponse;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class Generator
{

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
     * @var SystemConfig
     */
    private $systemConfig;

    public function __construct(
        Quote $quote,
        UrlInterface $urlInterface,
        PaymentData $paymentData,
        Session $checkoutSession,
        Http $request,
        Order $order,
        OrderSender $orderSender,
        SystemConfig $systemConfig
    )
    {
        $this->quote = $quote;
        $this->urlInterface = $urlInterface;
        $this->paymentData = $paymentData;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->systemConfig = $systemConfig;
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
        $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname');
        $auth = $this->systemConfig->getAuth($terminalId);

        $api = new TestAuthentication($auth);
        $response = $api->call();
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            if (! $response) {
                $this->restoreOrderFromOrderId($order->getIncrementId());
                $requestParams['result'] = 'error';
                $requestParams['message'] = 'Could not authenticate with API';
                return $requestParams;
            }

            $request = new PaymentRequest($auth);
            $request
                ->setTerminal($terminalName)
                ->setShopOrderId($order->getIncrementId())
                ->setAmount((float) $order->getGrandTotal())
                ->setCurrency($order->getOrderCurrencyCode())
                ->setCustomerInfo($this->setCustomer($order))
                ->setConfig($this->setConfig())
            ;

            if ($fraud = $this->systemConfig->getTerminalConfig($terminalId, 'fraud')) {
                $request->setFraudService($fraud);
            }

            if ($lang = $this->systemConfig->getTerminalConfig($terminalId, 'language')) {
                $request->setLanguage($lang);
            }

            if ($this->systemConfig->getTerminalConfig($terminalId, 'capture')) {
                $request->setType('paymentAndCapture');
            }
            // @todo add order lines

            try {
                /** @var \Altapay\Response\PaymentRequestResponse $response */
                $response = $request->call();
                $requestParams['result'] = 'success';
                $requestParams['formurl'] = $response->Url;
                // set before payment status
                $this->setCustomOrderStatus($order, Order::STATE_PENDING_PAYMENT, 'before');
                $order->save();
                // set notification
                $order->addStatusHistoryComment('Redirected to Altapay - Payment ID: ' . $response->PaymentRequestId);
                return $requestParams;
            } catch (ClientException $e) {
                $requestParams['result'] = 'error';
                $requestParams['message'] = $e->getResponse()->getBody();
            } catch (ResponseHeaderException $e) {
                $requestParams['result'] = 'error';
                $requestParams['message'] = $e->getHeader()->ErrorMessage;
            } catch (ResponseMessageException $e) {
                $requestParams['result'] = 'error';
                $requestParams['message'] = $e->getMessage();
            } catch (\Exception $e) {
                $requestParams['result'] = 'error';
                $requestParams['message'] = $e->getMessage();
            }

            $this->restoreOrderFromOrderId($order->getIncrementId());
            return $requestParams;
        }

        $this->restoreOrderFromOrderId($order->getIncrementId());
        $requestParams['result'] = 'error';
        $requestParams['message'] = 'error occured';
        return $requestParams;

    }

    public function restoreOrderFromOrderId($orderId)
    {
        $order = $this->loadOrderFromOrderId($orderId);
        if ($order->getId()) {
            $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
            $quote
                ->setIsActive(1)
                ->setReservedOrderId(null)
                ->save()
            ;
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    public function restoreOrderFromRequest(RequestInterface $request)
    {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        $order = $this->loadOrderFromCallback($response);
        if ($order->getId()) {
            $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
            $quote
                ->setIsActive(1)
                ->setReservedOrderId(null)
                ->save()
            ;
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    public function handleNotificationAction(RequestInterface $request)
    {
        $this->completeCheckout('Notifcation callback from Altapay', $request);
    }

    public function handleOkAction(RequestInterface $request)
    {
        $this->completeCheckout('OK callback from Altapay', $request);
    }

    /**
     * @param string $comment
     * @param RequestInterface $request
     */
    private function completeCheckout($comment, RequestInterface $request)
    {
        $callback = new Callback($request->getPostValue());
        $response = $callback->call();
        $order = $this->loadOrderFromCallback($response);

        if ($order->getId()) {
            // @todo Write data to DB
            $payment = $order->getPayment();
            $payment->setPaymentId($response->paymentId);
            $payment->setLastTransId($response->transactionId);
            $payment->setCcTransId($response->creditCardToken);
            $payment->save();
        }

        if (! $order->getEmailSent()) {
            $this->orderSender->send($order);
        }


        $this->setCustomOrderStatus($order, Order::STATE_PROCESSING, 'process');

        $order->addStatusHistoryComment($comment);
        $order->addStatusHistoryComment(sprintf(
            "Transaction ID: %s - Payment ID: %s - Credit card token: %s",
            $response->transactionId,
            $response->paymentId,
            $response->creditCardToken
        ));

        $order->setIsNotified(false);
        $order->save();
    }

    /**
     * @param CallbackResponse $response
     * @return Order
     */
    private function loadOrderFromCallback(CallbackResponse $response)
    {
        return $this->loadOrderFromOrderId($response->shopOrderId);
    }

    /**
     * @param string $orderId
     * @return Order
     */
    private function loadOrderFromOrderId($orderId)
    {
        $order = $this->order->loadByIncrementId($orderId);
        return $order;
    }

    private function setCustomOrderStatus(Order $order, $state, $statusKey)
    {
        $order->setState($state);
        if ($status = $this->systemConfig->getStatusConfig($statusKey)) {
            $this->order->setStatus($status);
        }
        $order->save();
    }

    private function setConfig()
    {
        $config = new Config();
        $config->setCallbackOk($this->urlInterface->getDirectUrl('sdmaltapay/index/ok'));
        $config->setCallbackFail($this->urlInterface->getDirectUrl('sdmaltapay/index/fail'));
        $config->setCallbackRedirect($this->urlInterface->getDirectUrl('sdmaltapay/index/redirect'));
        $config->setCallbackOpen($this->urlInterface->getDirectUrl('sdmaltapay/index/open'));
        $config->setCallbackNotification($this->urlInterface->getDirectUrl('sdmaltapay/index/notification'));
        $config->setCallbackVerifyOrder($this->urlInterface->getDirectUrl('sdmaltapay/index/verifyorder'));
        $config->setCallbackForm($this->urlInterface->getDirectUrl('sdmaltapay/index/callbackform'));
        return $config;
    }

    private function setCustomer(Order $order)
    {
        $billingAddress = new Address();
        if ($order->getBillingAddress()) {
            $address = $order->getBillingAddress()->convertToArray();
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
            $shippingAddress->Firstname = $address['firstname'];
            $shippingAddress->Lastname = $address['lastname'];
            $shippingAddress->Address = $address['street'];
            $shippingAddress->City = $address['city'];
            $shippingAddress->PostalCode = $address['postcode'];
            $shippingAddress->Region = $address['region'] ?: '0';
            $shippingAddress->Country = $address['country_id'];
            $customer->setShipping($shippingAddress);
        }

        return $customer;
    }

}
