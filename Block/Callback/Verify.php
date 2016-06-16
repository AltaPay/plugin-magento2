<?php
namespace SDM\Altapay\Block\Callback;

use Magento\Framework\View\Element\Template;

class Verify extends Template
{

    protected function _prepareLayout()
    {
        $this->setMessage('OKAY');
    }

}
