<?php
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
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
            $this->generator->restoreOrderFromRequest($this->getRequest());
            $post = $this->getRequest()->getPostValue();
            if (isset($post['error_message'])) {
                $msg = $post['error_message'];
            } else {
                $msg = 'Unknown response';
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        $this->logger->debug('messageManager - Error message: ' . $msg);
        $this->messageManager->addErrorMessage($msg);

        return $this->_redirect('*/*/failmessage', ['msg'=>$msg]);
    }

}
