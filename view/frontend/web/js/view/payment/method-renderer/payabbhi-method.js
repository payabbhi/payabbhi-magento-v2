define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList'
    ],
    function (Component, quote, $, ko, additionalValidators, setPaymentInformationAction, url, customer, placeOrderAction, fullScreenLoader, messageList) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payabbhi_Magento/payment/payabbhi-form',
                payabbhiDataFrameLoaded: false,
                payment_response: {}
            },
            getMerchantName: function() {
                return window.checkoutConfig.payment.payabbhi.merchant_name;
            },

            getAccessId: function() {
                return window.checkoutConfig.payment.payabbhi.access_id;
            },

            context: function() {
                return this;
            },

            isShowLegend: function() {
                return true;
            },

            getCode: function() {
                return 'payabbhi';
            },

            isActive: function() {
                return true;
            },

            isAvailable: function() {
                return this.payabbhiDataFrameLoaded;
            },

            handleError: function (error) {
                if (_.isObject(error)) {
                    this.messageContainer.addErrorMessage(error);
                } else {
                    this.messageContainer.addErrorMessage({
                        message: error
                    });
                }
            },

            initObservable: function() {
                var self = this;

                if(!this.payabbhiDataFrameLoaded) {
                    $.getScript("https://checkout.payabbhi.com/v1/checkout.js", function() { 
                        this.payabbhiDataFrameLoaded = true;
                    });
                }

                return this;
            },

            /**
             * @override
             */
             /** Process Payment */
            preparePayment: function (context, event) {
                var self = this,
                    billing_address;

                fullScreenLoader.startLoader();
                this.messageContainer.clear();

                this.amount = quote.totals()['base_grand_total'] * 100;
                billing_address = quote.billingAddress();

                this.user = {
                    name: billing_address.firstname + ' ' + billing_address.lastname,
                    contact: billing_address.telephone,
                    email: customer.customerData.email
                };

                if (!customer.isLoggedIn()) {
                    this.user.email = quote.guestEmail;
                }

                this.isPaymentProcessing = $.Deferred();

                $.when(this.isPaymentProcessing).done(
                    function () {
                        self.placeOrder();
                    }
                ).fail(
                    function (result) {
                        self.handleError(result);
                    }
                );

                self.getPayabbhiOrderId();

                return;
            },

            getPayabbhiOrderId: function () {
                var self = this;

                $.ajax({
                    type: 'POST',
                    url: url.build('payabbhi/payment/order'),

                    /**
                     * Success callback
                     * @param {Object} response
                     */
                    success: function (response) {
                        fullScreenLoader.stopLoader();
                        if (response.success) {
                            self.renderIframe(response);
                        } else {
                            self.isPaymentProcessing.reject(response.message);
                        }
                    },

                    /**
                     * Error callback
                     * @param {*} response
                     */
                    error: function (response) {
                        fullScreenLoader.stopLoader();
                        var message;
                        if (response.responseJSON !== undefined) {
                            message = response.responseJSON.message;
                        }
                        if (message === undefined ) {
                            message = response.message;
                        }
                        self.isPaymentProcessing.reject(message);
                    }
                });
            },

            renderIframe: function(data) {
                var self = this;

                this.merchant_order_id = data.merchant_order_id;

                var options = {
                    access_id: self.getAccessId(),
                    order_id: data.payabbhi_order_id,
                    amount: data.amount,
                    name: self.getMerchantName(),
                    handler: function (data) {
                        self.payment_response = data;
                        self.placeOrder(data);
                    },
                    modal: {
                        ondismiss: function() {
                            self.isPaymentProcessing.reject("Payment Cancelled");
                        }
                    },
                    description: 'Order #' + data.merchant_order_id,
                    notes: {
                        merchant_order_id: data.merchant_order_id
                    },
                    prefill: {
                        name: this.user.name,
                        contact: this.user.contact,
                        email: this.user.email
                    }
                };

                if (data.quote_currency !== 'INR')
                {
                    options.display_currency = data.quote_currency;
                    options.display_amount = data.quote_amount;
                }
                this.payabbhi = new Payabbhi(options);

                this.payabbhi.open();
            },

            getData: function() {
                return {
                    "method": this.item.method,
                    "po_number": null,
                    "additional_data": {
                        payment_id: this.payment_response.payment_id,
                        order_id: this.payment_response.order_id,
                        payment_signature: this.payment_response.payment_signature
                    }
                };
            }
        });
    }


);
