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
use SDM\Altapay\Controller\Index;
use Magento\Framework\Controller\ResultInterface;

class Ok extends Index
{

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws \Exception
     */
    public function execute()
    {
        $this->writeLog();
        $checkAvs = false;
        $post     = $this->getRequest()->getPostValue();
        $orderId  = $post['shop_orderid'];
        if (isset($post['avs_code']) && isset($post['avs_text'])) {
            $checkAvs = $this->generator->avsCheck(
                $this->getRequest(),
                strtolower($post['avs_code']),
                strtolower($post['avs_text'])
            );
        }
        if ($this->checkPost() && $checkAvs == false) {
            $this->generator->handleOkAction($this->getRequest());

            return $this->setSuccessPath($orderId);

        } else {
            $this->_eventManager->dispatch('order_cancel_after', ['order' => $this->order]);
            $this->generator->restoreOrderFromRequest($this->getRequest());

            return $this->_redirect('checkout');
        }
    }
}
