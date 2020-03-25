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
        "jquery",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/default",
        "mage/translate",
        "mage/url",
        "Magento_Vault/js/view/payment/vault-enabler",
        "Magento_Ui/js/model/messageList",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/seamlessformutils"
    ],
    function ($, Component, $t, url, VaultEnabler, messageList, Utils) {
        "use strict";
        return Component.extend({
            seamlessResponse: null,
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard",
                redirectAfterPlaceOrder: false
            },

            settings : {
                formIdSuffix: "_seamless_form",
                ERROR_COUNTER_STORAGE_KEY: "errorCounter",
                WPP_CLIENT_VALIDATION_ERROR_CODES: ["FE0001"],
                WPP_ERROR_PREFIX: "error_",
                MAX_ERROR_REPEAT_COUNT:3
            },

            button : {
                SUBMIT_ORDER: "wirecard_elasticengine_creditcard_submit"
            },

            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                if (!localStorage.getItem(this.settings.ERROR_COUNTER_STORAGE_KEY)) {
                    this.resetErrorsCounter();
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
             * Get the wpp_url
             */
            getPaymentPageScript: function () {
                return window.checkoutConfig.payment[this.getCode()].wpp_url;
            },

            seamlessFormInitVaultEnabler: function () {
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
            },

            /**
             * Handle form initialization
             */
            seamlessFormInit: function () {
                return Utils.seamlessFormInit.call(this);
            },

            /**
             * Prepare order to be placed
             * @param data,event
             */
            placeSeamlessOrder: function (data, event) {
                return Utils.placeSeamlessOrder.call(this, event, this.getCode() + "_seamless_form");
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
            },

            /**
             * Get the form id string
             */
            getFormId: function() {
                return this.getCode() + this.settings.formIdSuffix;
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
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vaultCode;
            },

            /**
             * @returns {bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            }
        });
    }
);
