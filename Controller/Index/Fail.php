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

            //Set order status, if available from the payment gateway
            switch ($post['status'])
            {
	            case 'cancelled':
	            	//TODO: Overwrite the message
				$msg = "Payment canceled";
		            $this->generator->handleCancelStatusAction($this->getRequest());
		            break;
	            case 'failed':
	            	//The consumer should be redirected to the payment step, where can select a new payment type
		            $this->generator->handleFailedStatusAction($this->getRequest());
		            break;

            	default:
		            $this->generator->handleOrderStateAction($this->getRequest());

            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

	    $this->messageManager->addErrorMessage(__($msg));
	    $resultRedirect = $this->resultRedirectFactory->create();
	    $customerRedirUrl = $this->_url->getUrl('checkout', array('_fragment' => 'payment'));
	    $resultRedirect->setPath($customerRedirUrl);
	    // TODO: refactor the redirect to a fail message
	    //return $this->_redirect('*/*/failmessage', ['msg'=>$msg]);

	    return $resultRedirect;
    }

}
