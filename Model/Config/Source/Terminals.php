<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2020 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Config\Source;

use Altapay\Response\TerminalsResponse;
use Magento\Framework\Option\ArrayInterface;
use SDM\Altapay\Model\SystemConfig;
use Psr\Log\LoggerInterface;

class Terminals implements ArrayInterface
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Terminals constructor.
     *
     * @param SystemConfig    $systemConfig
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfig $systemConfig, LoggerInterface $logger)
    {
        $this->systemConfig = $systemConfig;
        $this->logger       = $logger;
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
            $response    = $call->call();
            $terminals[] = ['value' => ' ', 'label' => '-- Please Select --'];
            foreach ($response->Terminals as $terminal) {
                $terminals[] = ['value' => $terminal->Title, 'label' => $terminal->Title];
            }
        } catch (\Exception $e) {
            $this->logger->debug('Exception', $e->getMessage());
        }
        // Sort the terminals alphabetically
        array_multisort(array_column($terminals, 'label'), SORT_ASC, SORT_NUMERIC, $terminals);

        return $terminals;
    }
}
