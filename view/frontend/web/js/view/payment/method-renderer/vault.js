/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

/*global WPP*/
define([
    "jquery",
    "Magento_Vault/js/view/payment/method-renderer/vault",
    "mage/url",
    "Wirecard_ElasticEngine/js/view/payment/method-renderer/seamlessformutils",
    "Wirecard_ElasticEngine/js/view/payment/method-renderer/variables"
],
    function ($, VaultComponent, url, Utils, variables) {
    "use strict";

    return VaultComponent.extend({
        defaults: {
            template: "Wirecard_ElasticEngine/payment/method-vault",
            redirectAfterPlaceOrder: false
        },
        /**
         * Get the form id string
         */
        getFormId: function() {
            return this.getId() + variables.settings.formIdTokenSuffix
        },
        /**
         * Get the wpp_url
         * return {String}
         */
        getPaymentPageScript: function () {
            return this.wppUrl;
        },
        /**
         * Constructs the ui initialization data object
         * return {Object}
         */
        getUiInitData() {
            return {
                txtype: this.wpp_txtype,
                token: this.getToken(),
            };
        },
        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },
        getData: function () {
            var result = null;
            $.ajax({
                url: url.build(variables.url.vault+this.getToken()),
                type: variables.method.get,
                dataType: variables.dataType.json,
                async: false,
                success: function (data) {
                    result = data;
                }
            });

            return {
                "method": result.method_code,
                "po_number": null,
                "additional_data": {
                    "token_id": result.token_id,
                    "recurring_payment" : true
                }
            };
        },
        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },
        /**
         * Handle the selected payment method and initialize form
         */
        selectPaymentMethod: function () {
            this._super();
            if($("#" + this.getId()).is(":checked") && $("#" + this.getFormId()).is(":empty")) {
                Utils.seamlessFormInit.call(this);
            }
            return true;
        },
        /**
         * Prepare order to be placed
         * @param data,event
         */
        placeTokenSeamlessOrder: function (data, event) {
            return Utils.placeSeamlessOrder.call(this, event, this.getFormId);
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
});
