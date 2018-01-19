/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'mage/translate',
    'mage/url'
], function ($, VaultComponent, $t, url) {
    'use strict';

    return VaultComponent.extend({
        token_id: null,
        defaults: {
            template: 'Magento_Vault/payment/form',
            redirectAfterPlaceOrder: false
        },
        initialize: function() {
            this._super();
            this.config = window.checkoutConfig.payment['wirecard_elasticengine_creditcard'];

            WirecardPaymentPage.seamlessRenderForm({
                requestData: this.config.seamless_request_data,
                wrappingDivId: 'wirecard_elasticengine_creditcard_seamless_form'
            });
        },

        seamlessFormSubmit: function() {
            WirecardPaymentPage.seamlessSubmitForm({
                onSuccess: this.seamlessFormSubmitSuccessHandler.bind(this),
                onError: this.seamlessFormSubmitErrorHandler.bind(this),
                wrappingDivId: 'wirecard_elasticengine_creditcard_seamless_form'
            });
        },

        seamlessFormSubmitSuccessHandler: function () {
            //this.token_id = response.token_id;
            this.placeOrder();
        },

        seamlessFormSubmitErrorHandler: function (response) {
            this.messageContainer.addErrorMessage({message: $t('An error occurred submitting the credit card form.')});

            console.error(response);
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

        getTokenFromHash: function () {
            console.log("publicHash: " + this.getToken());
            $.get(url.build("wirecard_elasticengine/frontend/vault?hash="+this.getToken()), function (data) {
                this.token_id = data.token_id;
            });

        },

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            this.getTokenFromHash();


            this.afterPlaceOrder();
        },

        afterPlaceOrder: function () {
            $.get(url.build("wirecard_elasticengine/frontend/callback"), function (data) {
                data['redirect-url'] = '';
                data['form-method'] = 'POST';
                data['form-url'] = 'https://c3-test.wirecard.com/acssim/app/bank';
                if (data['form-url']) {
                    var form = $('<form />', {action: data['form-url'], method: data['form-method']});
                    form.append($('<input />', {
                        type: 'hidden',
                        name: 'tokenId',
                        value: this.token_id
                    }));
                    form.appendTo('body').submit();
                } else {
                    window.location.replace(data['redirect-url']);
                }
            });
        }
    });
});
