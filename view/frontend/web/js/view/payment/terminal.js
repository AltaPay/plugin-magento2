/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
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
                type: 'terminal1',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal1-method'
            },
            {
                type: 'terminal2',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal2-method'
            },
            {
                type: 'terminal3',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal3-method'
            },
            {
                type: 'terminal4',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal4-method'
            },
            {
                type: 'terminal5',
                component: 'SDM_Altapay/js/view/payment/method-renderer/terminal5-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
