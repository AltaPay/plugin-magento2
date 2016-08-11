<?php
namespace SDM\Altapay\Observer;

use Altapay\Api\Payments\RefundCapturedReservation;
use Altapay\Response\RefundResponse;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Altapay\Model\SystemConfig;

class CreditmemoRefundObserver implements ObserverInterface
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
        /** @var \Magento\Sales\Api\Data\CreditmemoInterface $memo */
        $memo = $observer['creditmemo'];
        $creditOnline = $memo->getDoTransaction();
        if ($creditOnline) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $memo->getOrder();

            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();

            $refund = new RefundCapturedReservation($this->systemConfig->getAuth());
            $refund->setTransaction($payment->getLastTransId());
            /** @var RefundResponse $response */
            $response = $refund->call();
            if ($response->Result != 'Success') {
                throw new \InvalidArgumentException('Could not refund captured reservation');
            }
        }

    }
}
