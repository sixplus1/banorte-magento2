define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/model/quote'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Sixplus1_Banorte/payment/banorte',
                code: 'sixplus1_banorte'
            },

            isAvailable: function () {
                return quote.totals()['grand_total'] <= 0;
            },

            context: function(){
                return this;
            },

            getCode: function(){
                return this.code;
            },

            isActive: function(){
                return true;
            }

            
        });
    }
);
