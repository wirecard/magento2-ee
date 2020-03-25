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
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/constants"
    ],
    function ($, Component, $t, url, VaultEnabler, messageList, constants) {
        "use strict";
        return Component.extend({
            seamlessResponse: null,

            /**
             * @returns {exports.initialize}
             */
            initialize: function () {
                this._super();
                if (!localStorage.getItem(constants.settings.ERROR_COUNTER_STORAGE_KEY)) {
                    this.resetCounter();
                }
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
                                messageList.addErrorMessage({
                                    message: $t("credit_card_form_loading_error")
                                });
                            }
                        },
                        error: function (err) {
                            hideSpinner();
                            messageList.addErrorMessage({
                                message: $t("credit_card_form_loading_error")
                            });
                            console.error("Error : " + JSON.stringify(err));
                        }
                    });
                });
                let self = this;
                setTimeout(function(){
                    if (typeof WPP === "undefined") {
                        hideSpinner();
                        self.disableButtonById(constants.button.SUBMIT_ORDER);
                        messageList.addErrorMessage({
                            message: $t("credit_card_form_loading_error")
                        });
                    }
                }, 1000);
            },
            seamlessFormSubmitSuccessHandler: function (response) {
                this.hideSpinner();
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
                        messageList.addErrorMessage({
                            message: $t("credit_card_form_loading_error")
                        });
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
                localStorage.setItem(constants.settings.ERROR_COUNTER_STORAGE_KEY, "0");
            },
            /**
             * Increments error counter and returns it's value
             * @returns {number}
             */
            incrementCounter: function () {
                var counter = parseInt(localStorage.getItem(constants.settings.ERROR_COUNTER_STORAGE_KEY), 10);
                counter = parseInt(counter, 10) + 1;
                localStorage.setItem(constants.settings.ERROR_COUNTER_STORAGE_KEY, counter.toString());
                return counter;
            },
            seamlessFormInitErrorHandler: function (response) {
                console.error(response);
                this.disableButtonById(constants.button.SUBMIT_ORDER);
                let keys = Object.keys(response);
                let hasMessages = false;
                let self = this;
                keys.forEach(
                    function ( key ) {
                        if (key.startsWith(constants.settings.WPP_ERROR_PREFIX)) {
                            hasMessages = true;
                            messageList.addErrorMessage({
                                message: response[key]
                            });
                        }
                    }
                );
                if (!hasMessages) {
                    messageList.addErrorMessage({
                        message: $t("credit_card_form_loading_error")
                    });
                }
                if (this.incrementCounter() <= constants.settings.MAX_ERROR_REPEAT_COUNT) {
                    setTimeout(function () {
                        location.reload();
                    }, 3000);
                } else {
                    this.resetCounter();
                }
                this.hideSpinner();
            },
            seamlessFormSubmitErrorHandler: function (response) {
                console.error(response);
                let self = this;
                this.hideSpinner();
                let validErrorCodes = constants.settings.WPP_CLIENT_VALIDATION_ERROR_CODES;
                var isClientValidation = false;
                if (response.errors.length > 0) {
                    response.errors.forEach(
                        function ( item ) {
                            if (validErrorCodes.includes(item.error.code)) {
                                isClientValidation = true;
                                self.enableButtonById(constants.button.SUBMIT_ORDER);
                            } else {
                                self.showErrorMessage()
                                messageList.addErrorMessage({
                                    message: item.error.description
                                });
                            }
                        }
                    );
                }
                if (!isClientValidation) {
                    this.disableButtonById(constants.button.SUBMIT_ORDER);
                    setTimeout(function () {
                        location.reload();
                    }, 3000);
                }
            },
            seamlessFormSizeHandler: function () {
                this.resetCounter();
                this.hideSpinner();
                this.enableButtonById(constants.button.SUBMIT_ORDER);
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
                console.log(this.vaultEnabler);

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
                this.showSpinner();
                this.disableButtonById(constants.button.SUBMIT_ORDER);
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

            disableButtonById: function (id) {
                document.getElementById(id).disabled = true;
            },

            enableButtonById: function (id) {
                document.getElementById(id).disabled = false;
            }

        });
    }
);
