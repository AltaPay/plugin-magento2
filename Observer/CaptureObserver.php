<?php
namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\CaptureReservation;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Request\OrderLine;
use Altapay\Response\CaptureReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Logger\Monolog;
use SDM\Altapay\Model\SystemConfig;
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CaptureObserver implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Monolog
     */
    private $monolog;
    
    /**
     * @var Order
    */
    private $order;

    /**
     * @var productFactory
    */
    private $productFactory;

    public function __construct(SystemConfig $systemConfig, Monolog $monolog, Order $order, ProductFactory $productFactory
    ,ScopeConfigInterface $scopeConfig)
    {
        $this->systemConfig = $systemConfig;
        $this->monolog = $monolog;
        $this->order = $order;
        $this->productFactory = $productFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function getProductPrice($id)
    {
    $product = $this->productFactory->create();
    $productPriceById = $product->load($id)->getPrice();
    return $productPriceById;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws ResponseHeaderException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer['payment'];

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer['invoice'];
        $orderIncrementId = $invoice->getOrder()->getIncrementId();
        $orderObject = $this->order->loadByIncrementId($orderIncrementId);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode = $invoice->getStore()->getCode();
        
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $this->logPayment($payment, $invoice);

            $orderlines = [];
            $appliedRule = $invoice->getAppliedRuleIds();
            $couponCode = $invoice->getDiscountDescription();
            $couponCodeAmount = $invoice->getDiscountAmount();
            /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
            foreach ($invoice->getItems() as $item) {
                $id = $item->getProductId();
                $productOriginalPrice = $this->getProductPrice($id);
                $priceExcTax = $item->getPrice();
                $quantity = $item->getQty();
                if ((int) $this->scopeConfig->getValue('tax/calculation/price_includes_tax', $storeScope) === 1) {
                    //Handle only if we have coupon Code
                    if(empty($couponCode)){
                        $taxPercent = $item->getOrderItem()->getTaxPercent();
                        $taxCalculatedAmount = $priceExcTax *  ($taxPercent/100);
                        $taxAmount = (number_format($taxCalculatedAmount, 2, '.', '') * $quantity);
                    } else{
                        $taxAmount = ($productOriginalPrice - $priceExcTax) * $quantity;
                    }
                }else{
                    $taxAmount = $item->getTaxAmount();
                }
                if ($item->getPriceInclTax()) {
                    $taxPercent = $item->getTaxPercent();
                    $this->logItem($item);
                    $orderline = new OrderLine(
                        $item->getName(),
                        $item->getSku(),
                        $quantity,
                        $item->getPrice()
                    );
                    $orderline->setGoodsType('item');
                    $orderline->taxAmount = $taxAmount;
                    $orderlines[] = $orderline;
                }
            }
            
            if ((abs($couponCodeAmount) > 0) || !(empty($appliedRules))) {
                if(empty($couponCode)){
                    $couponCode = 'Cart Price Rule';
                }
                // Handling price reductions
                $orderline = new OrderLine(
                    $couponCode,
                    'discount',
                    1,
                    $couponCodeAmount
                );
                $orderline->setGoodsType('handling');
                $orderlines[] = $orderline;
            }

            if ($invoice->getShippingInclTax()) {
                $orderline = new OrderLine(
                    'Shipping',
                    'shipping',
                    1,
                    $invoice->getShippingInclTax()
                );
                $orderline->setGoodsType('shipment');
                $orderline->taxAmount = $invoice->getShippingTaxAmount();
                $orderlines[] = $orderline;
            }

            $api = new CaptureReservation($this->systemConfig->getAuth($storeCode));
            if ($invoice->getTransactionId()) {
                $api->setInvoiceNumber($invoice->getTransactionId());
            }

            $api->setAmount((float) $invoice->getGrandTotal());
            $api->setOrderLines($orderlines);
            $api->setTransaction($payment->getLastTransId());
            /** @var CaptureReservationResponse $response */
            try {
                $response = $api->call();
            } catch (ResponseHeaderException $e) {
                $this->monolog->addInfo(print_r($e->getHeader()));
                $this->monolog->addCritical('Response header exception: ' . $e->getMessage());
                throw $e;
            } catch (\Exception $e) {
                $this->monolog->addCritical('Exception: ' . $e->getMessage());
            }

            $rawresponse = $api->getRawResponse();
            if(!empty($rawresponse)){
				$body = $rawresponse->getBody();
				$this->monolog->addInfo('Response body: ' . $body);
            }

            //Update comments if capture fail
            $xml = simplexml_load_string($body);
            if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed') {
                $orderObject->addStatusHistoryComment('Capture failed: '. $xml->Body->MerchantErrorMessage)->setIsCustomerNotified(false);
                $orderObject->getResource()->save($orderObject);
            }
            
            $headdata = [];
            foreach ($rawresponse->getHeaders() as $k => $v) {
                $headdata[] = $k . ': ' . json_encode($v);
            }
            $this->monolog->addInfo('Response headers: ' . implode(", ", $headdata));
            
            if ($response->Result != 'Success') {
                throw new \InvalidArgumentException('Could not capture reservation');
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice\Item $item
     */
    protected function logItem($item)
    {
        $this->monolog->addInfo(
            sprintf(
                implode(' - ', [
                    'getSku: %s',
                    'getQty: %s',
                    'getDescription: %s',
                    'getPrice(): %s',
                    'getDiscountAmount(): %s',
                    'getPrice() - getDiscountAmount(): %s',
                    'getRowTotalInclTax: %s',
                    'getRowTotal: %s'
                ]),
                $item->getSku(),
                $item->getQty(),
                $item->getDescription(),
                $item->getPrice(),
                $item->getDiscountAmount(),
                $item->getPrice() - $item->getDiscountAmount(),
                $item->getRowTotalInclTax(),
                $item->getRowTotal()
            )
        );
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     */
    protected function logPayment($payment, $invoice)
    {
        $logs = [
            'invoice.getTransactionId: %s',
            'invoice->getOrder()->getIncrementId: %s',
            '$invoice->getGrandTotal(): %s',
            'getLastTransId: %s',
            'getAmountAuthorized: %s',
            'getAmountCanceled: %s',
            'getAmountOrdered: %s',
            'getAmountPaid: %s',
            'getAmountRefunded: %s',
        ];

        $this->monolog->addInfo(
            sprintf(
                implode(' - ', $logs),
                $invoice->getTransactionId(),
                $invoice->getOrder()->getIncrementId(),
                $invoice->getGrandTotal(),
                $payment->getLastTransId(),
                $payment->getAmountAuthorized(),
                $payment->getAmountCanceled(),
                $payment->getAmountOrdered(),
                $payment->getAmountPaid(),
                $payment->getAmountRefunded()
            )
        );
    }
}
