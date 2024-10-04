<?php

namespace Omitsis\RedsysGooglePay\Model\Config\Source;

use Magento\Payment\Model\Source\Cctype as PaymentCctype;

/**
 * Class Cctype
 */
class Cctype extends PaymentCctype
{
    
    /**
     * Allowed credit card types
     *
     * @return array
     */
    public function getAllowedTypes()
    {
        return ['AE', 'DI', 'IC', 'JCB', 'MC', 'VI'];
    }
}
