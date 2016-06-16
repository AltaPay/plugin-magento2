<?php
namespace SDM\Altapay\Block;

use Magento\Payment\Block\Info as BaseInfo;

class Info extends BaseInfo
{

    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        if ($transId = $this->getInfo()->getLastTransId()) {
            $data['Transaction Id'] = $transId;
        }

        if ($ccTransId = $this->getInfo()->getCcTransId()) {
            $data['Credit card token'] = $ccTransId;
        }
        
        if ($paymentId = $this->getInfo()->getPaymentId()) {
            $data['Payment ID'] = $paymentId;
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

}
