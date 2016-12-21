/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'SDM_Altapay/js/view/payment/method-renderer/terminal-abstract'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                terminal: '3'
            }
        });
    }
);
