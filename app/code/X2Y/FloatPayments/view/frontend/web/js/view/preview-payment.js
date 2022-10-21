/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'mage/template',
    'Magento_Ui/js/modal/modal'
], function ($, priceUtils, mageTemplate) {
    'use strict';

    $.widget('float.productPaymentPreview', {
        options: {
            config : {
                productPrice: 0,
                maxMonths: 0,
                priceFormat: {}
            }
        },

        paymentPreviewTemplate: "Or as low as <i><%- amount %> / month interest-free</i>, using your existing credit card.",

        /**
         * @private
         */
        _create: function () {
            this.getPaymentPlanPreview()
                .bindEvents();
        },

        /**
         * @param price
         * @returns {float.productPaymentPreview}
         */
        getPaymentPlanPreview: function (price) {
            let productPrice = price || this.options.config.productPrice,
                previewAmount = productPrice / this.options.config.maxMonths,
                messageHtml = mageTemplate(this.paymentPreviewTemplate, {
                    amount: priceUtils.formatPrice(previewAmount, this.options.config.priceFormat)
                });

            $('[data-role="message"]').html(messageHtml);
            $(this.element).show();

            return this;
        },

        bindEvents: function () {
            let self = this;
            $('[data-role="priceBox"]').on('priceUpdated', function (event, data) {
                if (data['finalPrice']) {
                    self.getPaymentPlanPreview(data['finalPrice'].amount);
                }
            });
        }
    });

    return $.float.productPaymentPreview;
});
