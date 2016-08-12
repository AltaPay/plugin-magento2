<?php
namespace SDM\Altapay\Model\Config\Source;

use Altapay\Api\Test\TestConnection;
use SDM\Altapay\Model\SystemConfig;

class Connection implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    public function __construct(SystemConfig $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        try {
            $response = new TestConnection($this->systemConfig->getApiConfig('productionurl'));
            $result = $response->call();
        } catch (\Exception $e) {
            $result = false;
        }

        return [
            ['value' => '', 'label' => $result->call() ? 'Connection successful' : 'Could not connect']
        ];
    }
}
