<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\CaptureReservation;
use Altapay\Exceptions\ResponseHeaderException;
use Altapay\Response\CaptureReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use SDM\Altapay\Model\SystemConfig;
use Magento\Sales\Model\Order;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Helper\Config as storeConfig;
use SDM\Altapay\Model\Handler\OrderLinesHandler;
use SDM\Altapay\Model\Handler\PriceHandler;
use SDM\Altapay\Model\Handler\DiscountHandler;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use SimpleXMLElement;
/**
 * Class CaptureObserver
 * Handle the invoice capture functionality.
 */
class CaptureObserver implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var Helper Data
     */
    private $helper;

    /**
     * @var Helper Config
     */
    private $storeConfig;
    /**
     * @var OrderLinesHandler
     */
    private $orderLines;
    /**
     * @var PriceHandler
     */
    private $priceHandler;
    /**
     * @var DiscountHandler
     */
    private $discountHandler;

    /**
     * CaptureObserver constructor.
     *
     * @param SystemConfig      $systemConfig
     * @param LoggerInterface   $logger
     * @param Order             $order
     * @param Data              $helper
     * @param storeConfig       $storeConfig
     * @param OrderLinesHandler $orderLines
     * @param PriceHandler      $priceHandler
     * @param DiscountHandler   $discountHandler
     */
    public function __construct(
        SystemConfig $systemConfig,
        LoggerInterface $logger,
        Order $order,
        Data $helper,
        storeConfig $storeConfig,
        OrderLinesHandler $orderLines,
        PriceHandler $priceHandler,
        DiscountHandler $discountHandler
    ) {
        $this->systemConfig    = $systemConfig;
        $this->logger          = $logger;
        $this->order           = $order;
        $this->helper          = $helper;
        $this->storeConfig     = $storeConfig;
        $this->orderLines      = $orderLines;
        $this->priceHandler    = $priceHandler;
        $this->discountHandler = $discountHandler;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws ResponseHeaderException
     */
    public function execute(Observer $observer)
    {
        $payment          = $observer['payment'];
        $invoice          = $observer['invoice'];
        $orderIncrementId = $invoice->getOrder()->getIncrementId();
        $orderObject      = $this->order->loadByIncrementId($orderIncrementId);
        $storeCode        = $invoice->getStore()->getCode();
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            //Create orderlines from order items
            $orderLines = $this->processInvoiceOrderLines($invoice);
            //Send request for payment refund
            $this->sendInvoiceRequest($invoice, $orderLines, $orderObject, $payment, $storeCode);
        }
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return array
     */
    private function processInvoiceOrderLines($invoice)
    {
        $couponCode       = $invoice->getDiscountDescription();
        $couponCodeAmount = $invoice->getDiscountAmount();
        //Return true if discount enabled on all items
        $discountAllItems = $this->discountHandler->allItemsHaveDiscount($invoice->getOrder()->getAllVisibleItems());
        //order lines for items
        $orderLines = $this->itemOrderLines($couponCodeAmount, $invoice, $discountAllItems);
        //send the discount into separate orderline if discount applied to all items
        if ($discountAllItems && abs($couponCodeAmount) > 0) {
            //order lines for discounts
            $orderLines[] = $this->orderLines->discountOrderLine($couponCodeAmount, $couponCode);
        }
        if ($invoice->getShippingInclTax() > 0) {
            //order lines for shipping
            $orderLines[] = $this->orderLines->handleShipping($invoice, $discountAllItems, false);
        }
        if (!empty($this->fixedProductTax($invoice))) {
            //order lines for FPT
            $orderLines[] = $this->orderLines->fixedProductTaxOrderLine($this->fixedProductTax($invoice));
        }

        return $orderLines;
    }

    /**
     * @param                                         $couponCodeAmount
     * @param InvoiceInterface                        $invoice
     * @param                                         $discountAllItems
     *
     * @return array
     */
    private function itemOrderLines($couponCodeAmount, $invoice, $discountAllItems)
    {
        $orderLines       = [];
        $storePriceIncTax = $this->storeConfig->storePriceIncTax($invoice->getOrder());
        foreach ($invoice->getAllItems() as $item) {
            $qty         = $item->getQty();
            $taxPercent  = $item->getOrderItem()->getTaxPercent();
            $productType = $item->getOrderItem()->getProductType();
            if ($qty > 0 && $productType != 'bundle' && $item->getPriceInclTax()) {
                $discountAmount = $item->getDiscountAmount();
                $originalPrice  = $item->getOrderItem()->getOriginalPrice();
                $totalPrice     = $originalPrice * $qty;

                if ($originalPrice == 0) {
                    $originalPrice = $item->getPriceInclTax();
                }

                if ($storePriceIncTax) {
                    $priceWithoutTax = $this->priceHandler->getPriceWithoutTax($originalPrice, $taxPercent);
                    $price           = $item->getPriceInclTax();
                    $unitPrice       = bcdiv($priceWithoutTax, 1, 2);
                    $taxAmount       = $this->priceHandler->calculateTaxAmount($priceWithoutTax, $taxPercent, $qty);
                } else {
                    $price           = $item->getPrice();
                    $unitPrice       = $originalPrice;
                    $taxAmount       = $this->priceHandler->calculateTaxAmount($unitPrice, $taxPercent, $qty);
                }
                $itemDiscountInformation = $this->discountHandler->getItemDiscountInformation(
                    $totalPrice,
                    $price,
                    $discountAmount,
                    $qty,
                    $discountAllItems,
                    $item,
                    $taxAmount
                );
                $discountedAmount        = $itemDiscountInformation['discount'];
                $orderLines[]            = $this->orderLines->itemOrderLine(
                    $item,
                    $unitPrice,
                    $discountedAmount,
                    $taxAmount,
                    $invoice->getOrder(),
                    false,
                    $discountAllItems
                );
                $roundingCompensation    = $this->priceHandler->compensationAmountCal(
                    $item,
                    $unitPrice,
                    $taxAmount,
                    $discountedAmount,
                    false
                );
                // check if rounding compensation amount, send in the separate orderline
                if ($roundingCompensation > 0 || $roundingCompensation < 0) {
                    $orderLines[] = $this->orderLines->compensationOrderLine(
                        "Compensation Amount",
                        "comp-" . $item->getOrderItem()->getItemId(),
                        $roundingCompensation
                    );
                }
            }
        }

        return $orderLines;
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return array
     */
    private function shippingTrackingInfo($invoice)
    {
        $trackingInfo     = [];
        $tracksCollection = $invoice->getOrder()->getTracksCollection();
        $trackItems       = $tracksCollection->getItems();

        if ($trackItems && is_array($trackItems)) {
            foreach ($trackItems as $track) {
                $trackingInfo[] = [
                    'shippingCompany' => $track->getTitle(),
                    'trackingNumber'  => $track->getTrackNumber()
                ];
            }
        }

        return $trackingInfo;
    }

    /**
     * @param InvoiceInterface      $invoice
     * @param array                 $orderLines
     * @param Order                 $orderObject
     * @param Payment               $payment
     * @param StoreManagerInterface $storeCode
     *
     * @throws ResponseHeaderException
     */
    private function sendInvoiceRequest($invoice, $orderLines, $orderObject, $payment, $storeCode)
    {
        $api = new CaptureReservation($this->systemConfig->getAuth($storeCode));
        if ($invoice->getTransactionId()) {
            $api->setInvoiceNumber($invoice->getTransactionId());
        }
        $api->setAmount((float)number_format($invoice->getGrandTotal(), 2, '.', ''));
        $api->setOrderLines($orderLines);
        $shippingTrackingInfo = $this->shippingTrackingInfo($invoice);
        /*send shipping tracking info if exists*/
        if (!empty($shippingTrackingInfo)) {
            $api->setTrackingInfo($shippingTrackingInfo);
        }

        $api->setTransaction($payment->getLastTransId());
        /** @var CaptureReservationResponse $response */
        try {
            $response = $api->call();
        } catch (ResponseHeaderException $e) {
            $this->logger->info("Exception: " . print_r($e->getHeader()));
            $this->logger->critical('Response header exception: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical('Exception: ' . $e->getMessage());
        }

        $rawResponse = $api->getRawResponse();
        if (!empty($rawResponse)) {
            $body = $rawResponse->getBody();
            $xml = json_encode(new SimpleXMLElement($body, LIBXML_NOCDATA));
            $this->logger->info('Response body', json_decode($xml, true));
            //Update comments if capture fail
            $xml = simplexml_load_string($body);
            if ($xml->Body->Result == 'Error' || $xml->Body->Result == 'Failed' || $xml->Body->Result == 'Incomplete') {
                $orderObject->addStatusHistoryComment('Capture failed: ' . $xml->Body->MerchantErrorMessage)
                            ->setIsCustomerNotified(false);
                $orderObject->getResource()->save($orderObject);
            }

            $headData = [];
            foreach ($rawResponse->getHeaders() as $k => $v) {
                $headData[] = $k . ': ' . json_encode($v);
            }
            $this->logger->info('Response headers: ', $headData);
        }
        if (!isset($response->Result) || $response->Result != 'Success') {
            throw new \InvalidArgumentException('Could not capture reservation');
        }
    }

    /**
     * @param InvoiceInterface $invoice
     *
     * @return float|int
     */
    public function fixedProductTax($invoice)
    {

        $weeTaxAmount = 0;
        foreach ($invoice->getAllItems() as $item) {
            $weeTaxAmount +=  $item->getWeeeTaxAppliedRowAmount();
        }

        return $weeTaxAmount;
    }
}
