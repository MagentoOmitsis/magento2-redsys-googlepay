<?php

namespace Omitsis\RedsysGooglePay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentMethodsHelperData;

class AdditionalConfigVars implements ConfigProviderInterface
{
    const GPAY_CODE = 'gpay';

    public function __construct(
        private readonly PaymentMethodsHelperData $paymentMethodsHelperData
    ) {
    }

    /**
     * Get additional config variables for Google Pay
     *
     * @return array
     */
    public function getConfig(): array
    {
        $gpayData = $this->paymentMethodsHelperData->getMethodInstance(self::GPAY_CODE);

        if (!$gpayData) {
            return [];
        }

        // GPay Info
        $additionalVariables['gpay_environment'] = $gpayData->getConfigData('environment');
        $additionalVariables['gpay_merchantId'] = $gpayData->getConfigData('merchantId');
        $additionalVariables['gpay_cc_types'] = $gpayData->getAvailableCardTypes();

        // Gateway Info
        $additionalVariables['gpay_gateway'] = $gpayData->getConfigData('gateway');
        $additionalVariables['gpay_gateway_merchantId'] = $gpayData->getConfigData('gateway_merchantId');
        $additionalVariables['gpay_gateway_merchantOrigin'] = $gpayData->getConfigData('gateway_merchantOrigin');
        $additionalVariables['gpay_gateway_merchantName'] = $gpayData->getConfigData('gateway_merchantName');

        return $additionalVariables;
    }
}
