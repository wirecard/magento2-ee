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
        "Magento_Vault/js/view/payment/vault-enabler"
    ],
    function ($, Component, $t, url, VaultEnabler) {
        "use strict";
        return Component.extend({
            seamlessResponse: null,
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard",
                redirectAfterPlaceOrder: false
            },

            settings : {
                ERROR_COUNTER_STORAGE_KEY: "errorCounter",
                WPP_CLIENT_VALIDATION_ERROR_CODES: ["FE0001"]
            },

            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                return this;
            },

            /**
             * Init config
             */
            initClientConfig: function () {
                this._super();
            },

            getPaymentPageScript: function () {
                return window.checkoutConfig.payment[this.getCode()].wpp_url;
            },

            seamlessFormInitVaultEnabler: function () {
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
            },

            seamlessFormInit: function () {
                let uiInitData = {"txtype": this.getCode()};
                let wrappingDivId = this.getCode() + "_seamless_form";
                let formSizeHandler = this.seamlessFormSizeHandler.bind(this);
                let formInitHandler = this.seamlessFormInitErrorHandler.bind(this);
                let hideSpinner = this.hideSpinner.bind(this);
                let messageContainer = this.messageContainer;
                this.showSpinner();
                // wait until WPP-js has been loaded
                $.getScript(this.getPaymentPageScript(), function () {
                    // Build seamless renderform with full transaction data
                    $.ajax({
                        url: url.build("wirecard_elasticengine/frontend/creditcard"),
                        type: "post",
                        data: uiInitData,
                        success: function (result) {
                            if ("OK" === result.status) {
                                let uiInitData = JSON.parse(result.uiData);
                                WPP.seamlessRender({
                                    requestData: uiInitData,
                                    wrappingDivId: wrappingDivId,
                                    onSuccess: formSizeHandler,
                                    onError: formInitHandler
                                });
                            } else {
                                hideSpinner();
                                messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                            }
                        },
                        error: function (err) {
                            hideSpinner();
                            messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                            console.error("Error : " + JSON.stringify(err));
                        }
                    });
                });
            },
            seamlessFormSubmitSuccessHandler: function (response) {
                this.seamlessResponse = response;
                this.resetCounter();
                this.placeOrder();
            },
            afterPlaceOrder: function () {
                if (this.seamlessResponse.hasOwnProperty("acs_url")) {
                    this.redirectCreditCard(this.seamlessResponse);
                } else {
                    // Handle redirect for Non-3D transactions
                    $.ajax({
                        url: url.build("wirecard_elasticengine/frontend/redirect"),
                        type: "post",
                        data: {
                            "data": this.seamlessResponse,
                            "method": "creditcard"
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
                let appendFormData = this.appendFormData.bind(this);
                $.ajax({
                    url: url.build("wirecard_elasticengine/frontend/callback"),
                    type: "post",
                    data: {"jsresponse": response},
                    success: function (result) {
                        if (result.data["form-url"]) {
                            let form = $("<form />", {
                                action: result.data["form-url"],
                                method: result.data["form-method"]
                            });
                            appendFormData(result.data, form);
                            form.appendTo("body").submit();
                        }
                    },
                    error: function (err) {
                        this.messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                        console.error("Error : " + JSON.stringify(err));
                    }
                });
            },
            appendFormData: function (data, form) {
                for (let key in data) {
                    if (key !== "form-url" && key !== "form-method") {
                        form.append($("<input />", {
                            type: "hidden",
                            name: key,
                            value: data[key]
                        }));
                    }
                }
            },
            /**
             * resets error counter to 0
             */
            resetCounter: function () {
                localStorage.setItem(this.settings.ERROR_COUNTER_STORAGE_KEY, "0");
            },
            /**
             * Increments error counter and returns it's value
             * @returns {number}
             */
            getCounter: function () {
                if (localStorage.getItem(this.settings.ERROR_COUNTER_STORAGE_KEY)) {
                    let counter = parseInt(localStorage.getItem(this.settings.ERROR_COUNTER_STORAGE_KEY));
                    counter += 1;
                    localStorage.setItem(this.settings.ERROR_COUNTER_STORAGE_KEY, counter.toString());
                } else {
                    localStorage.setItem(this.settings.ERROR_COUNTER_STORAGE_KEY, "0");
                }
                return parseInt(localStorage.getItem(this.settings.ERROR_COUNTER_STORAGE_KEY));
            },
            /**
             * Show error message in the frontend checkout page
             * @param errorMessage
             */
            showErrorMessage: function (errorMessage) {
                if (errors.length > 0) {
                    this.messageContainer.addErrorMessage({message: $t(errorMessage)});
                }
                if (this.getCounter() <= 3) {
                    setTimeout(function () {
                        location.reload();
                    }, 3000);
                } else {
                    this.resetCounter();
                }
                this.hideSpinner();
            },
            seamlessFormInitErrorHandler: function (response) {
                console.error(response);
                if (response.hasOwnProperty("error_1")) {
                    this.showErrorMessage(response.error_1);
                } else {
                    this.showErrorMessage(response, "credit_card_form_loading_error");
                }
            },
            seamlessFormSubmitErrorHandler: function (response) {
                console.error(response);
                let validErrorCodes = this.settings.WPP_CLIENT_VALIDATION_ERROR_CODES;
                let isClientValidation = false;
                let errorList = [];
                response.errors.forEach(
                    function ( item ) {
                        if (validErrorCodes.includes(item.error.code)) {
                            isClientValidation = true;
                        } else {
                            errorList.push(item.error.description);
                        }
                    }
                );
                if (!isClientValidation) {
                    this.showErrorMessage(errorList);
                }
            },
            seamlessFormSizeHandler: function () {
                this.hideSpinner();
                window.addEventListener("resize", this.resizeIFrame.bind(this));
                let seamlessForm = document.getElementById(this.getCode() + "_seamless_form");
                if (seamlessForm !== null) {
                    this.resizeIFrame(seamlessForm);
                }
            },
            resizeIFrame: function (seamlessForm) {
                let iframe = seamlessForm.firstElementChild;
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

                return true;
            },
            /**
             * Submit credit card request
             */
            seamlessFormSubmit: function() {
                WPP.seamlessSubmit({
                    wrappingDivId: this.getCode() + "_seamless_form",
                    onSuccess: this.seamlessFormSubmitSuccessHandler.bind(this),
                    onError: this.seamlessFormSubmitErrorHandler.bind(this)
                });
            },
            placeSeamlessOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                this.seamlessFormSubmit();
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
            },

            showSpinner: function () {
                $("body").trigger("processStart");
            },

            hideSpinner: function () {
                $("body").trigger("processStop");
            },

        });
    }
);
