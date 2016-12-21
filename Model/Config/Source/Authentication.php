<?php
namespace SDM\Altapay\Model\Config\Source;

use Altapay\Api\Test\TestAuthentication;
use Magento\Framework\Option\ArrayInterface;
use SDM\Altapay\Model\SystemConfig;

class Authentication implements ArrayInterface
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
            $response = new TestAuthentication($this->systemConfig->getAuth());
            $result = $response->call();
        } catch (\Exception $e) {
            $result = false;
        }

        return [
            ['value' => '', 'label' => $result ? 'Authentication successful' : 'Could not authenticate']
        ];
    }
}
