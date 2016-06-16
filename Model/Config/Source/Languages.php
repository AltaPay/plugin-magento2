<?php
namespace SDM\Altapay\Model\Config\Source;

use Magento\Config\Model\Config\Source\Locale;
use Magento\Framework\Option\ArrayInterface;

class Languages extends Locale implements ArrayInterface
{

    private static $allowedLanguages = [
        'cs', 'da', 'de', 'en', 'es', 'fi', 'fr', 'ja',
        'lt', 'nl', 'no', 'pl', 'sv','th', 'tr', 'zh',
        'et', 'it', 'pt', 'eu'
    ];

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $languages = [];
        $mainlanguages = parent::toOptionArray();
        foreach($mainlanguages as $keylang => $language) {
            list($key, $tmp) = explode('_', $language['value']);
            if (in_array($key, self::$allowedLanguages)) {
                $languages[$key] = $language;
            }
        }

        return array_merge([
            ['value' => '', 'label' => '- Auto select -']
        ], $languages);
    }

}
