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

define(
    [
        'jquery',
        'Wirecard_ElasticEngine/js/view/payment/method-renderer/default',
        'mage/translate',
        'mage/url',
        'Magento_Vault/js/view/payment/vault-enabler'
    ],
    function ($, Component, $t, url, VaultEnabler) {
        'use strict';
        return Component.extend({
            token_id: null,
            defaults: {
                template: 'Wirecard_ElasticEngine/payment/method-creditcard',
                redirectAfterPlaceOrder: false
            },
            seamlessFormInit: function () {
                WirecardPaymentPage.seamlessRenderForm({
                    requestData: this.config.seamless_request_data,
                    wrappingDivId: this.getCode() + '_seamless_form',
                    onSuccess: this.seamlessFormSizeHandler.bind(this),
                    onError: this.seamlessFormInitErrorHandler.bind(this)
                });
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
            },
            seamlessFormSubmit: function() {
                WirecardPaymentPage.seamlessSubmitForm({
                    onSuccess: this.seamlessFormSubmitSuccessHandler.bind(this),
                    onError: this.seamlessFormSubmitErrorHandler.bind(this),
                    wrappingDivId: this.getCode() + '_seamless_form'
                });
            },
            seamlessFormSubmitSuccessHandler: function (response) {
                this.token_id = response.token_id;
                this.placeOrder();
            },
            seamlessFormInitErrorHandler: function (response) {
                this.messageContainer.addErrorMessage({message: $t('An error occurred loading the credit card form. Please try again.')});

                console.error(response);
            },
            seamlessFormSubmitErrorHandler: function (response) {
                this.messageContainer.addErrorMessage({message: $t('An error occurred submitting the credit card form.')});

                console.error(response);

                setTimeout(function(){
                    location.reload();
                },3000);
            },
            seamlessFormSizeHandler: function () {
                window.addEventListener('resize', this.resizeIFrame.bind(this));
                this.resizeIFrame();
            },
            resizeIFrame: function () {
                var iframe = document.getElementById(this.getCode() + '_seamless_form').firstElementChild;
                if (iframe.clientWidth > 768) {
                    iframe.style.height = "267px";
                } else if (iframe.clientWidth > 460) {
                    iframe.style.height = "341px";
                } else {
                    iframe.style.height = "415px";
                }
            },

            getData: function () {
                return {
                    'method': this.getCode(),
                    'po_number': null,
                    'additional_data': {
                        'token_id': this.token_id,
                        'is_active_payment_token_enabler': this.vaultEnabler.isActivePaymentTokenEnabler()
                    }
                };
            },
            selectPaymentMethod: function () {
                this._super();
                this.resizeIFrame();

                return true;
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                if(!this.token_id) {
                    this.seamlessFormSubmit();

                    return false;
                }

                return this._super();
            },
            afterPlaceOrder: function () {
                $.get(url.build("wirecard_elasticengine/frontend/callback"), function (data) {
                    if (data['form-url']) {
                        var form = $('<form />', {action: data['form-url'], method: data['form-method']});

                        for (var i = 0; i < data['form-fields'].length; i++) {
                            form.append($('<input />', {
                                type: 'hidden',
                                name: data['form-fields'][i]['key'],
                                value: data['form-fields'][i]['value']
                            }));
                        }
                        form.appendTo('body').submit();
                    } else {
                        window.location.replace(data['redirect-url']);
                    }
                });
            },

            /**
             * @returns {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vaultCode;
            },

            /**
             * @returns {Bool}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            }
        });
    }
);
