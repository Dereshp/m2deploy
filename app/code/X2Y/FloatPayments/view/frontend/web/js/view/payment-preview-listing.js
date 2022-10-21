/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */

define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'mage/template',
], function ($, priceUtils, mageTemplate) {
    'use strict';

    $.widget('float.listingPaymentPreview', {
        options: {
            config : {
                maxMonths: 0,
                priceFormat: {}
            }
        },

        paymentPreviewTemplate: "Or as low as <i><%- amount %> / month</i> with ",

        /**
         * @private
         */
        _create: function () {
            let self = this,
                elements = $(document).find("[data-price-type='finalPrice'], [data-price-type='minPrice']");

            $.each(elements, function (i, element) {
                let productPrice = $(element).data("price-amount").toFixed();
                self.getPaymentPlanPreview(element, productPrice);
            });

            this.bindEvents();
        },

        /**
         * @param element
         * @param productPrice
         * @param isPriceUpdate
         */
        getPaymentPlanPreview: function (element, productPrice, isPriceUpdate = false) {
            let previewAmount = productPrice / this.options.config.maxMonths;
            let messageHtml = mageTemplate(this.paymentPreviewTemplate, {
                amount: priceUtils.formatPrice(previewAmount, this.options.config.priceFormat),
            });

            if (!isPriceUpdate) {
                $(element)
                    .parents('.price-box')
                    .after($('<p>', {
                        'class': 'payment-preview',
                        'html': messageHtml
                    }));
            } else {
                $(element).parent().find('.payment-preview').html(messageHtml);
            }
        },

        bindEvents: function () {
            let self = this;
            $('[data-role="priceBox"]').on('priceUpdated', function (event, data) {
                if (data['finalPrice']) {
                    self.getPaymentPlanPreview(event.currentTarget, data['finalPrice'].amount, true);
                }
            });
        }
    });

    return $.float.listingPaymentPreview;
});
