<?php

namespace SDM\Valitor\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;
use Valitor\Api\Test\TestAuthentication;
use Valitor\Api\Test\TestConnection;
use SDM\Valitor\Model\SystemConfig;
use Valitor\Authentication;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Config\Source\Allmethods;

/**
 * Class ConfigProvider
 * @package SDM\Valitor\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE  = 'sdm_valitor';

    /**
     * @var Data
     */
    private $data;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

     /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;
    
    /**
     * @var Config
     */
    protected $_paymentModelConfig;
    /**
     * @var allPaymentMethods
     */
    protected $allPaymentMethods;

    /**
     * ConfigProvider constructor.
     * @param Data $data
     * @param Escaper $escaper
     * @param UrlInterface $urlInterface
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(Data $data, Escaper $escaper,Allmethods $allPaymentMethods, UrlInterface $urlInterface, SystemConfig $systemConfig,
    ScopeConfigInterface $appConfigScopeConfigInterface, Config $paymentModelConfig
    )
    {
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_paymentModelConfig = $paymentModelConfig;
        $this->data = $data;
        $this->escaper = $escaper;
        $this->urlInterface = $urlInterface;
        $this->allPaymentMethods = $allPaymentMethods;
        $this->systemConfig = $systemConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $store = null;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $activePaymentMethod = $this->getActivePaymentMethod();
        return [
            'payment' => [
                self::CODE => [
                    'url' => $this->urlInterface->getDirectUrl($this->getData()->getConfigData('place_order_url')),
                    'auth' => $this->checkAuth(),
                    'connection' => $this->checkConn(),
                    'terminaldata' => $activePaymentMethod
                ]
            ]
        ];
    }

    public function getActivePaymentMethod(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode = $this->systemConfig->resolveCurrentStoreCode();
        $payments = $this->_paymentModelConfig->getActiveMethods();
        $methods = array();
        $allPaymentMethods = $this->data->getPaymentMethods();
        foreach ($allPaymentMethods as $paymentCode => $paymentModel) {
                $paymentTitle = $this->_appConfigScopeConfigInterface
            ->getValue('payment/'.$paymentCode.'/title', $storeScope, $storeCode);
                $selectedTerminal = $this->_appConfigScopeConfigInterface
            ->getValue('payment/'.$paymentCode.'/terminalname', $storeScope, $storeCode);
                $selectedTerminalStatus = $this->_appConfigScopeConfigInterface
            ->getValue('payment/'.$paymentCode.'/active', $storeScope, $storeCode);
            if($selectedTerminalStatus == 1){
                $methods[$paymentCode] = array(
                    'label' => $paymentTitle,
                    'value' => $paymentCode,
                    'terminalname' => $selectedTerminal,
                    'terminalstatus' => $selectedTerminalStatus
                );
            }
        }
        return $methods;
    }

    public function checkAuth()
    {
        $auth = 0;
        $response = new TestAuthentication($this->systemConfig->getAuth());
        if (!$response) {
            $result = false;
        } else {
            $result = $response->call();
        }
        if ($result) {
            $auth = 1;
        }

        return $auth;
    }

    public function checkConn()
    {
        $conn = 0;
        $response = new TestConnection($this->systemConfig->getApiConfig('productionurl'));
        if (!$response) {
            $result = false;
        } else {
            $result = $response->call();
        }
        if ($result) {
            $conn = 1;
        }
        return $conn;
    }

    /**
     * @return \Magento\Payment\Model\MethodInterface
     */
    protected function getData()
    {
        return $this->data->getMethodInstance('terminal1');
    }
}
