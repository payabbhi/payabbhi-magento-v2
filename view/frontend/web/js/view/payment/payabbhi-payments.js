define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push({
            type: 'payabbhi',
            component: 'Payabbhi_Magento/js/view/payment/method-renderer/payabbhi-method'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
