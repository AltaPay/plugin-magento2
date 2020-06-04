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
use Magento\Framework\DataObject;
use SDM\Valitor\Controller\Index;

class Request extends Index
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

        if ($this->checkPost()) {
            $params = $this->generator->createRequest(
                $this->getRequest()->getParam('paytype'),
                $this->getRequest()->getParam('orderid')
            );

            $result   = new DataObject();
            $response = $this->getResponse();
            $result->addData($params);

            return $response->representJson($result->toJson());
        }

        die('No post!?');
    }
}
