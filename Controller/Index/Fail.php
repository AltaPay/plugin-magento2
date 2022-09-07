<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use SDM\Altapay\Controller\Index;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;

class Fail extends Index
{

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $this->writeLog();
        $status = '';
        try {
            $this->generator->restoreOrderFromRequest($this->getRequest());
            $post                         = $this->getRequest()->getPostValue();
            $merchantError                = '';
            $status                       = strtolower($post['status']);
            $cardHolderMessageMustBeShown = false;

            if (isset($post['cardholder_message_must_be_shown'])) {
                $cardHolderMessageMustBeShown = $post['cardholder_message_must_be_shown'];
            }

            if (isset($post['error_message']) && isset($post['merchant_error_message'])) {
                if ($post['error_message'] != $post['merchant_error_message']) {
                    $merchantError = $post['merchant_error_message'];
                }
            }

            if (isset($post['error_message']) && $cardHolderMessageMustBeShown == "true") {
                $msg = $post['error_message'];
            } else {
                $msg = "Error with the Payment.";
            }

            //Set order status, if available from the payment gateway
            switch ($status) {
                case "cancelled":
                    //TODO: Overwrite the message
                    $msg = "Payment canceled";
                    $this->generator->handleCancelStatusAction($this->getRequest(), $status);
                    break;
                case "failed":
                case "error":
                case "incomplete":
                    $this->generator->handleFailedStatusAction($this->getRequest(), $msg, $merchantError, $status);
                    break;
                default:
                    $this->generator->handleOrderStateAction($this->getRequest());
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($status == 'failed' || $status == 'error' || $status == 'cancelled' || $status == 'incomplete') {
            $resultRedirect = $this->prepareRedirect('checkout/cart', [], $msg);
        } else {
            $resultRedirect = $this->prepareRedirect('checkout', ['_fragment' => 'payment'], $msg);
        }

        return $resultRedirect;
    }

    /**
     * @param        $routePath
     * @param null   $routeParams
     * @param string $message
     *
     * @return mixed
     */
    protected function prepareRedirect($routePath, $routeParams = null, $message = '')
    {
        if (!empty($message)) {
            $this->messageManager->addErrorMessage(__($message));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->_url->getUrl($routePath, $routeParams));

        return $resultRedirect;
    }
}
