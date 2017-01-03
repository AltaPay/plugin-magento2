<?php
namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\CaptureReservation;
use Altapay\Request\OrderLine;
use Altapay\Response\CaptureReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Logger\Monolog;
use SDM\Altapay\Model\SystemConfig;

class CaptureObserver implements ObserverInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var Monolog
     */
    private $monolog;

    public function __construct(SystemConfig $systemConfig, Monolog $monolog)
    {
        $this->systemConfig = $systemConfig;
        $this->monolog = $monolog;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer['payment'];

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $observer['invoice'];

        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
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

            $orderlines = [];
            /** @var \Magento\Sales\Model\Order\Invoice\Item $item */
            foreach ($invoice->getAllItems() as $item) {
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

                $orderlines[] = (new OrderLine(
                    $item->getDescription(),
                    $item->getSku(),
                    $item->getQty(),
                    $item->getPrice()
                ))->setGoodsType('item');

                if ($item->getDiscountAmount()) {
                    $orderlines[] = (new OrderLine(
                        $item->getDescription(),
                        $item->getSku(),
                        1,
                        (-1 * $item->getDiscountAmount())
                    ))->setGoodsType('handling');
                }

            }

            $api = new CaptureReservation($this->systemConfig->getAuth());
            if ($invoice->getTransactionId()) {
                $api->setInvoiceNumber($invoice->getTransactionId());
            }

            $api->setAmount($invoice->getGrandTotal());
            $api->setOrderLines($orderlines);
            $api->setTransaction($payment->getLastTransId());
            /** @var CaptureReservationResponse $response */
            $response = $api->call();
            if ($response->Result != 'Success') {
                throw new \InvalidArgumentException('Could not capture reservation');
            }
        }

    }
}
