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
        "jquery",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/default",
        "mage/translate",
        "mage/url",
        "Magento_Vault/js/view/payment/vault-enabler",
        "Magento_Checkout/js/model/quote",
        'Magento_Checkout/js/checkout-data'
    ],
    function ($, Component, $t, url, VaultEnabler, quote, checkoutData) {
        "use strict";
        return Component.extend({
            token_id: null,
            expiration_date: {},
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard",
                redirectAfterPlaceOrder: false
            },
            seamlessFormInit: function () {
                var wrappingDivId   = this.getCode() + "_seamless_form";
                var formSizeHandler = this.seamlessFormSizeHandler.bind(this);
                var formInitHandler = this.seamlessFormInitErrorHandler.bind(this);
                var messageContainer = this.messageContainer;

                $.ajax({
                    url : url.build("wirecard_elasticengine/frontend/creditcard"),
                    type : 'post',
                    success : function(result) {
                        if ('OK' === result.status) {
                            var uiInitData = JSON.parse(result.uiData);
                            WirecardPaymentPage.seamlessRenderForm({
                                requestData:   uiInitData,
                                wrappingDivId: wrappingDivId,
                                onSuccess:     formSizeHandler,
                                onError:       formInitHandler
                            });
                        } else {
                            messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                            console.error("Problem receive UI data");
                        }
                    },
                    error : function(err) {
                        messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                        console.error("Error : "+JSON.stringify(err));
                    }
                });

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
            },
            seamlessFormSubmit: function() {
                WirecardPaymentPage.seamlessSubmitForm({
                    onSuccess: this.seamlessFormSubmitSuccessHandler.bind(this),
                    onError: this.seamlessFormSubmitErrorHandler.bind(this),
                    wrappingDivId: this.getCode() + "_seamless_form"
                });
            },
            seamlessFormSubmitSuccessHandler: function (response) {
                if (response.hasOwnProperty("token_id")) {
                    this.token_id = response.token_id;
                    this.first_name = response.first_name;
                    this.last_name = response.last_name;
                } else if (response.hasOwnProperty("card_token") && response.card_token.hasOwnProperty("token")) {
                    this.token_id = response.card_token.token;

                    this.expiration_date = {};
                    var fields = ["expiration_month", "expiration_year"];
                    for (var part in fields) {
                        this.expiration_date[fields[part]] = response.card[fields[part]];
                    }
                }
                this.placeOrder();
            },
            seamlessFormInitErrorHandler: function (response) {
                this.messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                
                console.error(response);
            },
            seamlessFormSubmitErrorHandler: function (response) {
                this.messageContainer.addErrorMessage({message: $t("credit_card_form_submitting_error")});

                console.error(response);

                setTimeout(function(){
                    location.reload();
                },3000);
            },
            seamlessFormSizeHandler: function () {
                window.addEventListener("resize", this.resizeIFrame.bind(this));
                this.resizeIFrame();
            },
            resizeIFrame: function () {
                var iframe = document.getElementById(this.getCode() + "_seamless_form").firstElementChild;
                if (iframe) {
                    if (iframe.clientWidth > 768) {
                        iframe.style.height = "267px";
                    } else if (iframe.clientWidth > 460) {
                        iframe.style.height = "341px";
                    } else {
                        iframe.style.height = "415px";
                    }
                }
            },

            getData: function () {
                return {
                    "method": this.getCode(),
                    "po_number": null,
                    "additional_data": {
                        "token_id": this.token_id,
                        "is_active_payment_token_enabler": this.vaultEnabler.isActivePaymentTokenEnabler(),
                        "expiration_year": this.expiration_date.expiration_year,
                        "expiration_month": this.expiration_date.expiration_month,
                        "first_name": this.first_name,
                        "last_name": this.last_name
                    }
                };
            },
            selectPaymentMethod: function () {
                this._super();
                this.resizeIFrame();

                return true;
            },
            placeSeamlessOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                this.seamlessFormSubmit();
            },
            afterPlaceOrder: function () {
                $.get(url.build("wirecard_elasticengine/frontend/callback"), function (data) {
                    if (data["form-url"]) {
                        var form = $("<form />", {action: data["form-url"], method: data["form-method"]});

                        for (var i = 0; i < data["form-fields"].length; i++) {
                            form.append($("<input />", {
                                type: "hidden",
                                name: data["form-fields"][i]["key"],
                                value: data["form-fields"][i]["value"]
                            }));
                        }
                        form.appendTo("body").submit();
                    } else {
                        window.location.replace(data["redirect-url"]);
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
