<?php
namespace SDM\Altapay\Model\Config\Source;

use Altapay\Api\Test\TestAuthentication;
use SDM\Altapay\Model\SystemConfig;

class Authentication implements \Magento\Framework\Option\ArrayInterface
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
        $connection = new TestAuthentication($this->systemConfig->getAuth());
        return [
            ['value' => '', 'label' => $connection->call() ? 'Authentication successful' : 'Could not authenticate']
        ];
    }
}
