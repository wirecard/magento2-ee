/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define(
    [
        "jquery",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/default",
        "mage/translate",
        "mage/url",
        "Magento_Vault/js/view/payment/vault-enabler"
    ],
    function ($, Component, $t, url, VaultEnabler) {
        "use strict";
        return Component.extend({
            expiration_date: {},
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard"
            },
            seamlessFormInit: function () {
                let uiInitData = {"txtype": this.getCode()};
                let wrappingDivId = this.getCode() + "_seamless_form";
                let formSizeHandler = this.seamlessFormSizeHandler.bind(this);
                let formInitHandler = this.seamlessFormInitErrorHandler.bind(this);
                let messageContainer = this.messageContainer;

                // Build seamless renderform with full transaction data
                $.ajax({
                    url: url.build("wirecard_elasticengine/frontend/creditcard"),
                    type: 'post',
                    data: uiInitData,
                    success: function (result) {
                        if ('OK' === result.status) {
                            let uiInitData = JSON.parse(result.uiData);
                            WirecardPaymentPage.seamlessRenderForm({
                                requestData: uiInitData,
                                wrappingDivId: wrappingDivId,
                                onSuccess: formSizeHandler,
                                onError: formInitHandler
                            });
                        } else {
                            messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                        }
                    },
                    error: function (err) {
                        messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                        console.error("Error : " + JSON.stringify(err));
                    }
                });

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
            },
            seamlessFormSubmitSuccessHandler: function (response) {
                if (response.hasOwnProperty('acs_url')) {
                    this.redirectCreditCard(response);
                } else {
                    // Handle redirect for Non-3D transactions
                    $.ajax({
                        url: url.build("wirecard_elasticengine/frontend/redirect"),
                        type: 'post',
                        data: {
                            'data': response,
                            'method': 'creditcard'
                        }
                    }).done(function (data) {
                        // Redirect non-3D credit card payment response
                        window.location.replace(data["redirect-url"]);
                    });
                }
            },
            /**
             * Handle 3Ds credit card transactions within callback
             * @param response
             */
            redirectCreditCard: function (response) {
                let result = {};
                result.data = {};
                $.ajax({
                    url: url.build("wirecard_elasticengine/frontend/callback"),
                    type: 'post',
                    data: {'jsresponse': response},
                    success: function (result) {
                        if (result.data["form-url"]) {
                            let form = $("<form />", {
                                action: result.data["form-url"],
                                method: result.data["form-method"]
                            });
                            for (let key in result.data) {
                                if (key !== "form-url" && key !== "form-method") {
                                    form.append($("<input />", {
                                        type: "hidden",
                                        name: key,
                                        value: result.data[key]
                                    }));
                                }
                            }
                            form.appendTo("body").submit();
                        }
                    },
                    error: function (err) {
                        messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                        console.error("Error : " + JSON.stringify(err));
                    }
                });
            },
            seamlessFormInitErrorHandler: function (response) {
                this.messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                console.error(response);
            },
            seamlessFormSubmitErrorHandler: function (response) {
                this.messageContainer.addErrorMessage({message: $t("credit_card_form_submitting_error")});

                console.error(response);

                setTimeout(function () {
                    location.reload();
                }, 3000);
            },
            seamlessFormSizeHandler: function () {
                window.addEventListener("resize", this.resizeIFrame.bind(this));
                this.resizeIFrame();
            },
            resizeIFrame: function () {
                let iframe = document.getElementById(this.getCode() + "_seamless_form").firstElementChild;
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
                        "is_active_payment_token_enabler": this.vaultEnabler.isActivePaymentTokenEnabler()
                    }
                };
            },
            selectPaymentMethod: function () {
                this._super();
                this.resizeIFrame();

                return true;
            },

            /**
             * Submit credit card request
             */
            afterPlaceOrder: function () {
                WirecardPaymentPage.seamlessSubmitForm({
                    wrappingDivId: this.getCode() + "_seamless_form",
                    onSuccess: this.seamlessFormSubmitSuccessHandler.bind(this),
                    onError: this.seamlessFormSubmitErrorHandler.bind(this)
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
