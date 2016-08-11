<?php
namespace SDM\Altapay\Model;

use Altapay\Authentication;
use Magento\Config\Model\Config\Backend\Encrypted;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SystemConfig
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Encrypted
     */
    private $encrypter;

    public function __construct(ScopeConfigInterface $scopeConfig, Encrypted $encrypter)
    {
        $this->scopeConfig = $scopeConfig;
        $this->encrypter = $encrypter;
    }

    static public function getTerminalCodes()
    {
        return [
            Terminal1::METHOD_CODE,
            Terminal2::METHOD_CODE,
            Terminal3::METHOD_CODE,
            Terminal4::METHOD_CODE,
            Terminal5::METHOD_CODE
        ];
    }

    /**
     * @return Authentication
     */
    public function getAuth()
    {
        $login = $this->getApiConfig('api_log_in');
        $password = $this->encrypter->processValue($this->getApiConfig('api_pass_word'));
        $baseurl = $this->getApiConfig('productionurl');
        if (empty($baseurl)) {
            $baseurl = null;
        }

        return new Authentication($login, $password, $baseurl);
    }

    /**
     * @param string $configKey
     * @return string
     */
    public function getStatusConfig($configKey)
    {
        return $this->scopeConfig->getValue(sprintf(
            'payment/altapay_status/%s',
            $configKey
        ));
    }

    /**
     * @param int $terminalId
     * @param string $configKey
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getTerminalConfig($terminalId, $configKey)
    {
        return $this->scopeConfig->getValue(sprintf(
            'payment/terminal%d/%s',
            $terminalId,
            $configKey
        ));
    }

    /**
     * @param string $configKey
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getApiConfig($configKey)
    {
        return $this->scopeConfig->getValue(sprintf(
            'payment/altapay_config/%s',
            $configKey
        ));
    }

}
