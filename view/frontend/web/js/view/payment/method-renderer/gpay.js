define(
    [
        'jquery',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'googlePayLibrary'
    ],
    function (
        $,
        customerData,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        quote,
        checkoutData,
        additionalValidators,
        url
    ) {
        'use strict';

        let
            gpay_environment = window.checkoutConfig.gpay_environment,
            gpay_merchantId = window.checkoutConfig.gpay_merchantId,
            gateway = window.checkoutConfig.gpay_gateway,
            gateway_merchantId = window.checkoutConfig.gpay_gateway_merchantId,
            gateway_merchantOrigin = window.checkoutConfig.gpay_gateway_merchantOrigin,
            gateway_merchantName = window.checkoutConfig.gpay_gateway_merchantName,
            urlOk = 'mpay/google/success/paymentToken/'
        ;

        /**
         * Define the version of the Google Pay API referenced when creating your
         * configuration
         *
         * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|apiVersion in PaymentDataRequest}
         */
        const baseRequest = {
            apiVersion: 2,
            apiVersionMinor: 0
        };

        /**
         * Card networks supported by your site and your gateway
         *
         * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
         * @todo confirm card networks supported by your site and gateway
         */
        const allowedCardNetworks = window.checkoutConfig.gpay_cc_types;

        /**
         * Card authentication methods supported by your site and your gateway
         *
         * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
         * @todo confirm your processor supports Android device tokens for your
         * supported card networks
         */
        const allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];

        /**
         * Identify your gateway and your site's gateway merchant identifier
         *
         * The Google Pay API response will return an encrypted payment method capable
         * of being charged by a supported gateway after payer authorization
         *
         * @todo check with your gateway on the parameters to pass
         * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#gateway|PaymentMethodTokenizationSpecification}
         */
        const tokenizationSpecification = {
            type: 'PAYMENT_GATEWAY',
            parameters: {
                'gateway': gateway,
                'gatewayMerchantId': gateway_merchantId
            }
        };

        /**
         * Describe your site's support for the CARD payment method and its required
         * fields
         *
         * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
         */
        const baseCardPaymentMethod = {
            type: 'CARD',
            parameters: {
                allowedAuthMethods: allowedCardAuthMethods,
                allowedCardNetworks: allowedCardNetworks
            }
        };

        /**
         * Describe your site's support for the CARD payment method including optional
         * fields
         *
         * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#CardParameters|CardParameters}
         */
        const cardPaymentMethod = Object.assign(
            {},
            baseCardPaymentMethod,
            {
                tokenizationSpecification: tokenizationSpecification
            }
        );

        /**
         * An initialized google.payments.api.PaymentsClient object or null if not yet set
         *
         * @see {@link getGooglePaymentsClient}
         */
        let paymentsClient = null;


        return Component.extend({
            defaults: {
                template: 'Omitsis_RedsysGooglePay/payment/gpay'
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    const paymentDataRequest = this.getGooglePaymentDataRequest();
                    const paymentsClient = this.getGooglePaymentsClient();

                    paymentsClient.loadPaymentData(paymentDataRequest).then(function(paymentData){
                        self.paymentToken = btoa(paymentData.paymentMethodData.tokenizationData.token);
                        placeOrder = placeOrderAction(self.getData(), false, self.messageContainer);
                        $.when(placeOrder).fail(function () {
                            self.isPlaceOrderActionAllowed(true);
                        }).done(self.afterPlaceOrder.bind(self));
                        return true;
                    }).catch(function(err){
                        console.error(err);
                    });
                }
                return false;
            },

            /**
             * Configure support for the Google Pay API
             *
             * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#PaymentDataRequest|PaymentDataRequest}
             * @returns {object} PaymentDataRequest fields
             */
            getGooglePaymentDataRequest: function() {
                const paymentDataRequest = Object.assign({}, baseRequest);
                paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
                paymentDataRequest.transactionInfo = this.getGoogleTransactionInfo();
                paymentDataRequest.merchantInfo = {
                    merchantId: gpay_merchantId,
                    merchantName: gateway_merchantName,
                    merchantOrigin: gateway_merchantOrigin
                };

                paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];

                return paymentDataRequest;

            },

            /**
             * Provide Google Pay API with a payment amount, currency, and amount status
             *
             * @see {@link https://developers.google.com/pay/api/web/reference/request-objects#TransactionInfo|TransactionInfo}
             * @returns {object} transaction info, suitable for use as transactionInfo property of PaymentDataRequest
             */

            getGoogleTransactionInfo: function() {
                let transactionInfo = {};
                transactionInfo.totalPriceStatus = 'FINAL';
                transactionInfo.countryCode = 'ES';
                transactionInfo.currencyCode = "EUR";

                if (quote.totals().total_segments !== null && quote.totals().total_segments.length > 0) {
                    transactionInfo.displayItems = [];
                    quote.totals().total_segments.forEach(
                        function (totalSegment) {
                            if (totalSegment.code === 'grand_total') {
                                transactionInfo.totalPriceLabel = totalSegment.title;
                                transactionInfo.totalPrice = totalSegment.value.toFixed(2);
                            } else if (totalSegment.value !== null) {
                                let item = {};
                                if (totalSegment.code === 'subtotal') {
                                    item.type = 'SUBTOTAL';
                                } else {
                                    item.type = 'LINE_ITEM';
                                }
                                item.label = totalSegment.title;
                                item.price = totalSegment.value.toFixed(2);
                                transactionInfo.displayItems.push(item);
                            }
                        }
                    );
                }

                return transactionInfo;
            },

            /**
             * Return an active PaymentsClient or initialize
             *
             * @see {@link https://developers.google.com/pay/api/web/reference/client#PaymentsClient|PaymentsClient constructor}
             * @returns {google.payments.api.PaymentsClient} Google Pay API client
             */
            getGooglePaymentsClient: function() {
                if ( paymentsClient === null ) {
                    paymentsClient = new google.payments.api.PaymentsClient({
                        environment: gpay_environment,
                        paymentDataCallbacks: {
                            onPaymentAuthorized: this.onPaymentAuthorized
                        }
                    });
                }
                return paymentsClient;

            },

            /**
             * Handles authorize payments callback intents.
             *
             * @param {object} paymentData response from Google Pay API after a payer approves payment through user gesture.
             * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentData object reference}
             *
             * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentAuthorizationResult}
             * @returns Promise<{object}> Promise of PaymentAuthorizationResult object to acknowledge the payment authorization status.
             */

            onPaymentAuthorized: function(paymentData) {
                return new Promise(function(resolve, reject){
                    processPayment(paymentData)
                        .then(function() {
                            resolve({
                                transactionState: 'SUCCESS',
                                paymentData: paymentData
                            });
                        })
                        .catch(function() {
                            resolve({
                                transactionState: 'ERROR',
                                error: {
                                    intent: 'PAYMENT_AUTHORIZATION',
                                    message: 'Insufficient funds',
                                    reason: 'PAYMENT_DATA_INVALID'
                                }
                            });
                        });
                });

                /**
                 * Process payment data returned by the Google Pay API
                 *
                 * @param {object} paymentData response from Google Pay API after user approves payment
                 * @see {@link https://developers.google.com/pay/api/web/reference/response-objects#PaymentData|PaymentData object reference}
                 */
               function processPayment(paymentData) {
                    return new Promise(function(resolve, reject) {
                        setTimeout(function() {
                            let paymentToken;
                            paymentToken = paymentData.paymentMethodData.tokenizationData.token;
                            resolve({paymentData: paymentData});
                        }, 3000);
                    });

                }

            },

            afterPlaceOrder: function () {
                var sections = ['cart'];
                customerData.invalidate(sections);
                customerData.reload(sections, true);
                $.mage.redirect(url.build(urlOk + this.paymentToken));
            }

        });
    }
);
