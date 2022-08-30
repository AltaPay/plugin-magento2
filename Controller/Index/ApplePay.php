<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use SDM\Altapay\Model\SystemConfig;
use Altapay\Api\Test\TestAuthentication;
use SDM\Altapay\Api\Payments\CardWalletSession;
use SDM\Altapay\Helper\Config as storeConfig;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;

class ApplePay extends Action
{
    /**
     * @var Helper Config
     */
    private $storeConfig;
    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;

    /**
     * ApplePay constructor.
     *
     * @param Context               $context
     * @param storeConfig           $storeConfig
     * @param SystemConfig          $systemConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        storeConfig $storeConfig,
        SystemConfig $systemConfig,
        StoreManagerInterface $storeManager,
        UrlInterface $urlInterface
    ) {
        parent::__construct($context);
        $this->storeConfig   = $storeConfig;
        $this->systemConfig  = $systemConfig;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $storeCode     = $this->getStoreCode();
        $validationUrl = $this->getRequest()->getParam('validationUrl');
        $terminalName = $this->getRequest()->getParam('termminalid');
        $currentUrl = $this->_urlInterface->getBaseUrl();
        $domain = parse_url($currentUrl, PHP_URL_HOST);
        $auth     = $this->systemConfig->getAuth($storeCode);
        $api      = new TestAuthentication($auth);
        $response = $api->call();
        if (!$response) {
            return false;
        }
        $request = new CardWalletSession($auth);
        $request->setTerminal($terminalName)
                ->setValidationUrl($validationUrl)
                ->setDomain($domain);

        $response = $request->call();
        if ($response->Result === 'Success') {
            echo json_encode($response->ApplePaySession);
        }
    }

    /**
     * Get Store code
     *
     * @return string
     */
    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }
}