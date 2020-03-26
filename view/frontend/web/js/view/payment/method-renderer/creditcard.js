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
        "Magento_Vault/js/view/payment/vault-enabler",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/seamlessformutils",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/variables"
    ],
    function (Component, VaultEnabler, Utils, variables) {
        "use strict";
        return Component.extend({
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard",
                redirectAfterPlaceOrder: false
            },
            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                if (!localStorage.getItem(variables.localStorage.counterKey)) {
                    localStorage.setItem(variables.localStorage.counterKey, variables.localStorage.initValue);
                }
                return this;
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
             * Check if vault is enabled
             * @returns {bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },
            /**
             * Get the wpp_url
             * return {String}
             */
            getPaymentPageScript: function () {
                return window.checkoutConfig.payment[this.getCode()].wpp_url;
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
             */
            getFormId: function() {
                return this.getCode() + variables.settings.formIdSuffix;
            },
            /**
             * Constructs the ui initialization data object
             * return {Object}
             */
            getUiInitData() {
                return {"txtype": this.getCode()};
            },
            /**
             * Get the data
             */
            getData: function () {
                return {
                    "method": this.getCode(),
                    "po_number": null,
                    "additional_data": {
                        "is_active_payment_token_enabler": this.vaultEnabler.isActivePaymentTokenEnabler()
                    }
                };
            },
            /**
             * Handle the selected payment method
             */
            selectPaymentMethod: function() {
                this._super();
                return true;
            },
            /**
             * Handle form initialization
             */
            seamlessFormInit: function () {
                Utils.seamlessFormInit.call(this);
            },

            /**
             * Prepare order to be placed
             * @param data,event
             */
            placeSeamlessOrder: function (data, event) {
                Utils.placeSeamlessOrder.call(this, event, this.getFormId);
            },
            /**
             * Handle post order creation operations
             */
            afterPlaceOrder: function () {
                Utils.afterPlaceOrder.call(this);
            },
            /**
             * Handle 3Ds credit card transactions within callback
             * @param response
             */
            redirectCreditCard: function (response,err) {
                Utils.redirectCreditCard.call(this,response, err);
            }
        });
    }
);
