<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2020 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use SDM\Valitor\Controller\Index;

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
