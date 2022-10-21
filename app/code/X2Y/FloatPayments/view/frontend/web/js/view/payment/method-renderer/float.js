/**
 * @author    X2Y.io Dev Team
 * @copyright Copyright (c) X2Y.io, Inc. (https://x2y.io/)
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Ui/js/model/messageList',
        'mage/translate',
        'X2Y_FloatPayments/js/action/order/create',
        'jquery'
    ],
    function (
        Component,
        additionalValidators,
        setPaymentInformationAction,
        messageContainer,
        $t,
        createOrderAction,
        $,
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'X2Y_FloatPayments/payment/form',
            },

            getCode: function () {
                return 'float';
            },

            getPaymentLogoSrc: function () {
                return require.toUrl('X2Y_FloatPayments/images/logo-solid.svg');
            },

            /**
             * Order place action.
             */
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                let self = this;

                if (this.validate() && additionalValidators.validate() && this.isPlaceOrderActionAllowed() === true) {
                    $.when(
                        this.setPaymentInformation()
                    ).done(
                        this.createFloatOder.bind(this)
                    ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                        }
                    );

                    // return true;
                }

                return false;
            },

            /**
             * {Function}
             */
            setPaymentInformation: function () {
                return setPaymentInformationAction(
                    messageContainer,
                    {
                        method: this.getCode()
                    }
                );
            },

            /**
             * {Function}
             */
            createFloatOder: function () {
                let self = this;
                $.when(createOrderAction(messageContainer))
                    .fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(
                    function(url) {
                        if (url) {
                            return window.location.href = url;
                        }

                        messageContainer.addErrorMessage({
                            message: $t('Float Payments is not available at the moment. Please try again later.')
                        });
                    }
                );
            }
        });
    }
);
