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
                type: 'sixplus1_banorte',
                component: 'Sixplus1_Banorte/js/view/payment/method-renderer/banorte'
            }
        );

        return Component.extend({});
    }
);