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

            //Set order status, if available from the payment gateway
            switch ($post['status']) {
                case 'cancelled':
                    //TODO: Overwrite the message
                    $msg = "Payment canceled";
                    $this->generator->handleCancelStatusAction($this->getRequest());
                    break;
                case ('failed' || 'error'):
                    $this->generator->handleFailedStatusAction($this->getRequest(), $msg, $merchantErrorMsg, $responseStatus);
                    break;

                default:
                    $this->generator->handleOrderStateAction($this->getRequest());
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($post['status'] == 'failed' || $post['status'] == 'error') {
            $resultRedirect = $this->prepareRedirect('checkout/cart', array(), $msg);
        } else {
            $resultRedirect = $this->prepareRedirect('checkout', array('_fragment' => 'payment'), $msg);
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
