define([
    'uiComponent',
    'jquery',
    'mage/storage',
    'Magento_Customer/js/model/customer',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'jquery/ui',
], function (
    Component,
    $,
    storage,
    customer,
    $t,
    alert
    ) {
        'use strict';

        return Component.extend({
            defaults : {
                cartId: '',
                element: $('#review-order-form'),
                pleaseWaitLoader: $('span.please-wait')
            },

            initialize: function (config, node) {
                this._super();
                this.element.on('submit', $.proxy(this._placeFloatOrder, this));
            },

            _placeFloatOrder: function (e) {
                let self = this;
                if (e) e.preventDefault();

                $.when(this._createOrderAction())
                    .done(function(url) {
                        if (url) {
                            return window.location.href = url;
                        }

                        alert({
                            content: $t('Float Payments is not available at the moment. Please try again later.')
                        });
                    })
                    .always(function () {
                        self.pleaseWaitLoader.hide();
                    });
            },

            _createOrderAction: function () {
                const urlGuest = 'rest/V1/guest-carts/:cartId/create-float-order';
                const urlCustomer = 'rest/V1/carts/mine/create-float-order';
                let serviceUrl = window.BASE_URL + urlGuest;
                const payload = {
                    cartId: this.cartId
                };

                if (customer.isLoggedIn()) {
                    serviceUrl = window.BASE_URL + urlCustomer;
                }

                return storage.post(
                    serviceUrl,
                    JSON.stringify(payload),
                    true,
                    'application/json',
                    {}
                );
            }
        });
    }
);
