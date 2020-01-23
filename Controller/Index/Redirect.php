<?php
namespace SDM\Valitor\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use SDM\Valitor\Controller\Index;

class Redirect extends Index
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

        $page = $this->pageFactory->create();
        return $page;
    }
}
