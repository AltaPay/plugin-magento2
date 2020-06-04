<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2020 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use SDM\Valitor\Response\TerminalsResponse;
use SDM\Valitor\Model\SystemConfig;

class Version extends Field
{
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Version constructor.
     *
     * @param Context      $context
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Context $context,
        SystemConfig $systemConfig
    ) {
        parent::__construct($context);
        $this->systemConfig = $systemConfig;
    }


    /**
     * Render module version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = '';
        try {
            $call = new \Valitor\Api\Others\Terminals($this->systemConfig->getAuth());
            /** @var TerminalsResponse $response */
            $response  = $call->call();
            $terminals = [];

            foreach ($response->Terminals as $terminal) {
                $creditCard = false;
                foreach ($terminal->Natures as $nature) {
                    if ($nature->Nature == "CreditCard") {
                        $creditCard = true;
                    }
                }
                $terminals[] = [
                    'title'      => $terminal->Title,
                    'creditCard' => $creditCard
                ];
            }

            $html .= "<tr id='row_terminals_data'>";
            $html .= "<td class='label'><input type='hidden' id='terminal_data_obj' value='" . json_encode($terminals)
                     . "'></td>";
            $html .= " <td></td>";
            $html .= " <td></td>";
            $html .= "</tr>";

        } catch (\Exception $e) {
        }

        return $html;
    }
}
