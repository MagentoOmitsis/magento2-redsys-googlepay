define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'gpay',
                component: 'Omitsis_RedsysGooglePay/js/view/payment/method-renderer/gpay'
            },
        );
        return Component.extend({});
    }
);
