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
        $response = new TestConnection($this->systemConfig->getTerminalConfig(1, 'productionurl'));
        return [
            ['value' => '', 'label' => $response->call() ? 'Connection successful' : 'Could not connect']
        ];
    }
}
