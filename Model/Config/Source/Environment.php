<?php

namespace Omitsis\RedsysGooglePay\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
class Environment implements OptionSourceInterface
{

    /**
     * Options getter for dropdown menus in Magento admin
     *
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }
        return $ret;
    }

    /**
     * Return available options as an associative array
     *
     * @return array
     */
    private function toArray()
    {
        $array = [
            'TEST' => __('Sandbox'),
            'PRODUCTION' => __('Production')
        ];
        return $array;
    }
}
