<?php

namespace Omitsis\RedsysGooglePay\Model\Payment;

/**
 * GPay payment method model
 */
class GPay extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    const PAYMENT_CODE = 'gpay';
    protected $_code = self::PAYMENT_CODE;

    const KEY_CC_TYPES = 'cc_types';

    const CC_TYPES_MAPPER = [
        'AE' => 'AMEX',
        'DI' => 'DISCOVER',
        'IC' => 'INTERAC',
        'JCB' => 'JCB',
        'MC' => 'MASTERCARD',
        'VI' => 'VISA'
    ];

    /**
     * Get configuration data
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        return parent::getConfigData($field, $storeId);
    }

    /**
     * Get available card types
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAvailableCardTypes($storeId = null): array
    {
        $value = $this->getConfigData(self::KEY_CC_TYPES, $storeId);
        $ccTypes = !empty($value) ? explode(',', $value) : [];

        $availableCardTypes = [];
        foreach (self::CC_TYPES_MAPPER as $key => $value) {
            if (in_array($key, $ccTypes)) {
                $availableCardTypes[] = $value;
            }
        }
        return $availableCardTypes;
    }
}
