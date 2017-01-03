<?php
namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\CaptureReservation;
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
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer['order'];

        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $logs = [
                'order.getIncrementId: %s',
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
                    $order->getIncrementId(),
                    $payment->getLastTransId(),
                    $payment->getAmountAuthorized(),
                    $payment->getAmountCanceled(),
                    $payment->getAmountOrdered(),
                    $payment->getAmountPaid(),
                    $payment->getAmountRefunded()
                )
            );

            $api = new CaptureReservation($this->systemConfig->getAuth());
            $api->setTransaction($payment->getLastTransId());
            /** @var CaptureReservationResponse $response */
            $response = $api->call();
            if ($response->Result != 'Success') {
                throw new \InvalidArgumentException('Could not capture reservation');
            }
        }

    }
}
