/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define([
    "jquery",
    "Magento_Vault/js/view/payment/method-renderer/vault",
    "mage/url"
], function ($, VaultComponent, url) {
    "use strict";

    return VaultComponent.extend({
        defaults: {
            template: "Magento_Vault/payment/form"
        },

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
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
                url: url.build("wirecard_elasticengine/frontend/vault?hash="+this.getToken()),
                type: "GET",
                dataType: "json",
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

        placeOrder: function () {
            return this._super();
        }
    });
});
