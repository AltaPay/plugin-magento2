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

class Failmessage extends Index
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
        $msg = $this->getRequest()->getParam('msg');
        $this->altapayLogger->addDebugLog('messageManager - Error message', $msg);
        $this->messageManager->addErrorMessage($msg);

        return $this->_redirect('checkout', ['_fragment' => 'payment']);
    }
}
