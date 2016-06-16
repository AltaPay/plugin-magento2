<?php
namespace SDM\Altapay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Model\SystemConfig;

class BeforePaymentObserver implements ObserverInterface
{

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

        $payment = $observer['payment'];
        if (in_array($payment->getMethod(), SystemConfig::getTerminalCodes())) {
            $order = $payment->getOrder();
            // Dont send any mails until payment is complete
            $order->setCanSendNewEmailFlag(false);
        }

    }

}
