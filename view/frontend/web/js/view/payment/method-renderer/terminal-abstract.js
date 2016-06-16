/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [

        'Magento_Checkout/js/view/payment/default',
        'Magento_Customer/js/customer-data',
        'SDM_Altapay/js/action/set-payment'
    ],
    function (Component, storage, Action) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'SDM_Altapay/payment/terminal',
                terminal: '1'
            },

            redirectAfterPlaceOrder: false,

            placeOrder: function () {
                var self = this;
                if (self.validate()) {
                    self.selectPaymentMethod();
                    Action(
                        this.messageContainer,
                        this.terminal
                    );
                }
            }

        });
    }
);
