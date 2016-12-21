<?php
namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\ReleaseReservation;
use Altapay\Response\ReleaseReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Model\SystemConfig;

class OrderCancelObserver implements ObserverInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer['order'];

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $api = new ReleaseReservation($this->systemConfig->getAuth());
            $api->setTransaction($payment->getLastTransId());
            /** @var ReleaseReservationResponse $response */
            $response = $api->call();
            if ($response->Result != 'Success') {
                throw new \InvalidArgumentException('Could not release reservation');
            }
        }

    }
}
