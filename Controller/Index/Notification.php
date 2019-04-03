<?php
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use SDM\Altapay\Controller\Index;

class Notification extends Index
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
			if ($this->checkPost()) {
            $post = $this->getRequest()->getPostValue();
            //Set order status, if available from the payment gateway
            $merchantErrorMsg = '';
            $responseStatus = '';
            if (isset($post['error_message'])) {
                $msg = $post['error_message'];
                if($post['error_message'] != $post['error_message']){
				  $merchantErrorMsg = $post['merchant_error_message'];
				}
                $responseStatus = $post['status'];
            } else {
                $msg = 'Unknown response';
            }
            switch ($post['status']) {
                case 'cancelled':
                    $msg = "Payment canceled";
                    $this->generator->handleCancelStatusAction($this->getRequest(),$responseStatus);
                    break;
                case ('failed' || 'error'):
                    $this->generator->handleFailedStatusAction($this->getRequest(), $msg, $merchantErrorMsg, $responseStatus);
                    break;
                case ('success' || 'succeed'):
                    $this->generator->handleNotificationAction($this->getRequest());
                    break;         
                default:
                    $this->generator->handleCancelStatusAction($this->getRequest(),$responseStatus);
            }
          } 
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($post['status'] != 'success' || $post['status'] != 'succeed') {
            $resultRedirect = $this->prepareRedirect('checkout/cart', array(), $msg);
        }

        return $resultRedirect;
    }
    
     private function prepareRedirect($routePath, $routeParams = null, $message = '')
    {
        if ($message != '') {
            $this->messageManager->addErrorMessage(__($message));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $customerRedirUrl = $this->_url->getUrl($routePath, $routeParams);
        $resultRedirect->setPath($customerRedirUrl);

        return $resultRedirect;
    }
}
