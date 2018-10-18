<?php
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use SDM\Altapay\Controller\Index;

class Failmessage extends Index
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
        $msg = $this->getRequest()->getParam('msg');
	    $this->logger->debug('messageManager - Error message: ' . $msg);
        $this->messageManager->addErrorMessage($msg);

	    return $this->_redirect('checkout', ['_fragment' => 'payment']);
    }
}
