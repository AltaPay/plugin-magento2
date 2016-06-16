<?php
namespace SDM\Altapay\Model\Config\Source;

use Altapay\Response\TerminalsResponse;
use SDM\Altapay\Model\SystemConfig;

class Terminals implements \Magento\Framework\Option\ArrayInterface
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
        $terminals = [];
        try {
            $call = new \Altapay\Api\Others\Terminals($this->systemConfig->getAuth());
            /** @var TerminalsResponse $response */
            $response = $call->call();
            foreach($response->Terminals as $terminal) {
                $terminals[] = ['value' => $terminal->Title, 'label' => $terminal->Title];
            }
        } catch (\Exception $e) {}
        return $terminals;
    }
}
