<?php

namespace Omitsis\RedsysGooglePay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class GatewayName
 */
class GatewayName implements ArrayInterface
{

    /**
     *  Payment Processors
     */
    const PROCESSOR_REDSYS = 'redsys';

    /**
     * Available Payment Processors
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::PROCESSOR_REDSYS,
                'label' => 'Redsys'
            ]
        ];
    }
}
