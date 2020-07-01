/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

/* globals WPP */

define(
    [
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/default",
        "Wirecard_ElasticEngine/js/view/payment/seamless-vault-enabler",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/seamlessformutils",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/constants",
        "Magento_Checkout/js/model/quote",
        "Magento_SalesRule/js/action/set-coupon-code",
        "Magento_SalesRule/js/action/cancel-coupon",
    ],
    function (ParentPaymentMethod, VaultEnabler, SeamlessCreditCardUtils, SeamlessCreditCardConstants, quote, setCoupon, cancelCoupon) {
        "use strict";
        return ParentPaymentMethod.extend({
            seamlessResponse: null,
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard",
                redirectAfterPlaceOrder: false
            },

            previousBillingAddress: quote.billingAddress(),
            newBillingAddress: null,
            isOnSelect: false,
          
            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                if (!localStorage.getItem(SeamlessCreditCardConstants.localStorage.counterKey)) {
                    localStorage.setItem(SeamlessCreditCardConstants.localStorage.counterKey, SeamlessCreditCardConstants.localStorage.initValue);
                }
                let self = this;
                setCoupon.registerSuccessCallback(this.getResetFormFunction());
                cancelCoupon.registerSuccessCallback(this.getResetFormFunction());
                quote.billingAddress.subscribe(function () {
                    let currentBillingAddress = quote.billingAddress();
                    self.newBillingAddress = currentBillingAddress;
                    if (self.isCreditCardSelected() && ((
                        ((JSON.stringify(self.previousBillingAddress) !== JSON.stringify(currentBillingAddress)) &&
                            currentBillingAddress !== null) ||
                        self.isSameShippingAndBillingAddress()) &&
                        self.isOnSelect === false)
                    ) {
                        self.seamlessFormInit();
                        self.previousBillingAddress = currentBillingAddress;
                    }
                });
                return this;
            },

            /**
             *  Reset the form after coupon apply/cancel
             */
            getResetFormFunction: function() {
                let self = this;
                return function() {
                    if (self.isCreditCardSelected())  {
                        self.seamlessFormInit();
                    }
                }
            },

            /**
             *  Get current billing address
             */
            getNewBillingAddress: function() {
                if (this.isCreditCardSelected()) {
                    this.newBillingAddress = quote.billingAddress();
                    this.isOnSelect = false;
                }
            },

            /**
             * Check if credit card radio button is selected
             * @returns {boolean}
             */
            isCreditCardSelected: function() {
                let creditCardRadioButton = document.getElementById(SeamlessCreditCardConstants.id.creditCardRadioButton);
                return !!(creditCardRadioButton) && (creditCardRadioButton.checked);
            },

            /**
             * Check if same shipping and billing checkbox is selected
             * @returns {boolean}
             */
            isSameShippingAndBillingAddress: function() {
                let sameShippingAndBilling = document.getElementById(SeamlessCreditCardConstants.id.sameShippingAndBillingAddress);
                return !!(sameShippingAndBilling) && (sameShippingAndBilling.checked);
            },

            /**
             * Init config
             */
            initClientConfig: function () {
                this._super();
            },

            /**
             * Get the vault code
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vaultCode;
            },

            /**
             * Get the form submit button id
             */
            getSubmitBtnId: function() {
                return SeamlessCreditCardConstants.button.submitOrder;
            },

            /**
             * Check if vault is enabled
             * @returns {Boolean}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * Initialize the vault enabler
             */
            seamlessFormInitVaultEnabler: function () {
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
            },
          
            /**
             * Get the form id string
             * return {String}
             */
            getFormId: function() {
                return this.getCode() + SeamlessCreditCardConstants.settings.formIdSuffix;
            },

            /**
             * Constructs the ui initialization data object
             * return {Object}
             */
            getUiInitData() {
                let payload = {
                    txtype: SeamlessCreditCardConstants.data.wppTxType,
                    billingAddress: JSON.stringify(this.newBillingAddress)
                };
                this.newBillingAddress = null;
                return payload;
            },

            /**
             * Get the data
             * return {Object}
             */
            getData: function () {
                //this payload is needed in this format from magento
                /*eslint-disable */
                let data =  {
                    method: this.getCode(),
                    po_number: null,
                    additional_data: {
                        is_active_payment_token_enabler: false,
                    }
                };
                /*eslint-enable */

                this.vaultEnabler.visitAdditionalData(data);

                return data;
            },

            /**
             * Handle the selected payment method
             */
            selectPaymentMethod: function() {
                this.isOnSelect = true;
                this._super();
                return true;
            },

            /**
             * Handle form initialization
             */
            seamlessFormInit: function () {
                this.getNewBillingAddress();
                SeamlessCreditCardUtils.seamlessFormInit.call(this);
            },

            /**
             * Prepare order to be placed
             * @param {Object} data
             * @param {Object} event
             */
            placeSeamlessOrder: function (data, event) {
                SeamlessCreditCardUtils.placeSeamlessOrder.call(this, event, this.getFormId);
            },

            /**
             * Handle post order creation operationsgetPaymentPageScript
             */
            afterPlaceOrder: function () {
                SeamlessCreditCardUtils.afterPlaceOrder.call(this);
            },

            /**
             * Handle 3Ds credit card transactions within callback
             * @param response
             */
            processThreeDPayment: function (response) {
                SeamlessCreditCardUtils.processThreeDPayment.call(this,response);
            }
        });
    }
);
