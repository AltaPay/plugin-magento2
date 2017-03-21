<?php
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use SDM\Altapay\Controller\Index;

class Fail extends Index
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
        $this->generator->restoreOrderFromRequest($this->getRequest());
        $msg = $this->getRequest()->getPostValue()['error_message'];
        $this->logger->debug('messageManager - Error message: ' . $msg);
        $this->messageManager->addWarningMessage($msg);
        return $this->_redirect('checkout');
    }

}
