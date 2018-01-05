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
        try {
            $order = $this->generator->restoreOrderFromRequest($this->getRequest());
            $post = $this->getRequest()->getPostValue();
            if (isset($post['error_message'])) {
                $msg = $post['error_message'];
            } else {
                $msg = 'Unknown response';
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        $this->logger->debug('messageManager - Error message: ' . $msg . ' - Order found: ' . $order ? 'Yes' : 'No');
        $this->messageManager->addWarningMessage($msg);

        if ($order) {
            return $this->_redirect('checkout');
        }

        return $this->_redirect('checkout/cart');
    }

}
