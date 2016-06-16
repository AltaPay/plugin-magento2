<?php
namespace SDM\Altapay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data;

class ConfigProvider implements ConfigProviderInterface
{

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

    public function __construct(Data $data, Escaper $escaper, UrlInterface $urlInterface)
    {
        $this->data = $data;
        $this->escaper = $escaper;
        $this->urlInterface = $urlInterface;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'sdm_altapay' => [
                    'url' => $this->urlInterface->getDirectUrl($this->getData()->getConfigData('place_order_url'))
                ]
            ]
        ];
    }

    /**
     * @return \Magento\Payment\Model\MethodInterface
     */
    private function getData()
    {
        return $this->data->getMethodInstance('terminal1');
    }

}
