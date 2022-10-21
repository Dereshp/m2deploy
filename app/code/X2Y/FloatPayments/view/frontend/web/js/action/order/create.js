/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 * @api
 */
define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader'
], function (
    quote,
    urlBuilder,
    storage,
    errorProcessor,
    customer,
    fullScreenLoader
) {
    'use strict';

    return function (messageContainer) {
        let serviceUrl,
            payload;

        payload = {
            cartId: quote.getQuoteId()
        };

        /**
         * Checkout for guest and registered customer.
         */
        if (!customer.isLoggedIn()) {
            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/create-float-order', {
                cartId: quote.getQuoteId()
            });
        } else {
            serviceUrl = urlBuilder.createUrl('/carts/mine/create-float-order', {});
        }

        fullScreenLoader.startLoader();

        return storage.post(
            serviceUrl,
            JSON.stringify(payload)
        ).fail(
            function (response) {
                errorProcessor.process(response, messageContainer);
            }
        ).always(
            function () {
                fullScreenLoader.stopLoader();
            }
        );
    };
});
