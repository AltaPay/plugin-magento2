<?php
namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\CaptureReservation;
use Altapay\Response\CaptureReservationResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Model\SystemConfig;

class CaptureObserver implements ObserverInterface
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
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer['payment'];

        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
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
