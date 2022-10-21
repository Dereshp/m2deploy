/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
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
                type: 'float',
                component: 'X2Y_FloatPayments/js/view/payment/method-renderer/float'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
