<?php
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use SDM\Altapay\Controller\Index;

class Ok extends Index
{

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->writeLog();

        if ($this->checkPost()) {
            $this->generator->handleOkAction($this->getRequest());
            return $this->_redirect('checkout/onepage/success');
        } else {
            $this->generator->restoreOrderFromRequest($this->getRequest());
            return $this->_redirect('checkout');
        }
    }

}
