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
        $errorMessage = $this->generator->restoreOrderFromRequest($this->getRequest());
        var_dump('fail', $errorMessage);
        exit;
        $this->messageManager->addErrorMessage($errorMessage);
        return $this->_redirect('checkout');
    }

}
