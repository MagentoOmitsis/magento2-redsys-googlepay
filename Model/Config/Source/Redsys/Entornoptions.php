<?php

namespace Omitsis\RedsysGooglePay\Model\Config\Source\Redsys;

class Entornoptions implements \Magento\Framework\Option\ArrayInterface
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
	public function toArray()
	{
		$array = [
            0 => __('Sandbox'),
            1 => __('Production')
        ];
        return $array;
	}
	
}
?>