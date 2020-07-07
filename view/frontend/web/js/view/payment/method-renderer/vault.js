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
    "Wirecard_ElasticEngine/js/view/payment/method-renderer/constants"
],
    function ($, VaultComponent, url, SeamlessCreditCardUtils, SeamlessCreditCardConstants) {
    "use strict";

    return VaultComponent.extend({
        seamlessResponse: null,
        defaults: {
            template: "Wirecard_ElasticEngine/payment/method-vault",
            redirectAfterPlaceOrder: false
        },

        /**
         * Get the form id string
         */
        getFormId: function() {
            return this.getId() + SeamlessCreditCardConstants.settings.formIdTokenSuffix;
        },

        /**
         * Get the form submit button id
         */
        getSubmitBtnId: function() {
            return SeamlessCreditCardConstants.button.submitOrderVaulted;
        },

        /**
         * Constructs the ui initialization data object
         * return {Object}
         */
        getUiInitData() {
            return {
                txtype: SeamlessCreditCardConstants.data.wppTxType,
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

        /**
         * Get the data
         */
        getData: function () {
            var result = null;
            $(SeamlessCreditCardConstants.tag.body).trigger(SeamlessCreditCardConstants.spinner.stop);
            $.ajax({
                url: url.build(SeamlessCreditCardConstants.routes.vaultController+this.getToken()),
                type: SeamlessCreditCardConstants.method.get,
                dataType: SeamlessCreditCardConstants.dataType.json,
                async: false,
                success: (data) => {
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
                SeamlessCreditCardUtils.seamlessFormInit.call(this);
            }
            return true;
        },

        /**
         * Prepare order to be placed
         * @param data,event
         * @param event
         */
        placeTokenSeamlessOrder: function (data, event) {
            return SeamlessCreditCardUtils.placeSeamlessOrder.call(this, event, this.getFormId);
        },

        /**
         * Handle post order creation operations
         */
        afterPlaceOrder: function () {
            SeamlessCreditCardUtils.afterPlaceOrder.call(this);
        },

        /**
         * Handle 3Ds credit card transactions within callback
         * @param response
         * @param err
         */
        processThreeDPayment: function (response,err) {
            SeamlessCreditCardUtils.processThreeDPayment.call(this,response, err);
        }
    });
});
