/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define(
    [
        'jquery',
        "mage/url",
        "mage/translate",
        "Magento_Ui/js/model/messageList",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/variables"
    ],

    function ($, url, $t, messageList, variables) {

        function seamlessFormSizeHandler () {
            setErrorsCounter("0");
            hideSpinner();
            enableButtonById(variables.button.SUBMIT_ORDER);
            //todo:getFormId has this in it
            let seamlessForm = document.getElementById(this.getFormId());
            window.addEventListener("resize", resizeIFrame);
            if (seamlessForm !== null) {
                resizeIFrame(seamlessForm);
            }
        }
        function showSpinner () {
            $("body").trigger("processStart");
        };
        function hideSpinner () {
            $("body").trigger("processStop");
        };

        function disableButtonById(id) {
            document.getElementById(id).disabled = true;
        };
        function enableButtonById(id) {
            document.getElementById(id).disabled = false;
        };
        function resizeIFrame(seamlessForm) {
            let iframe = seamlessForm.firstElementChild;
            if (iframe) {
                if (iframe.clientWidth > variables.screenSize.medium) {
                    iframe.style.height = variables.iFrameHeightSize.small;
                } else if (iframe.clientWidth > variables.screenSize.small) {
                    iframe.style.height = variables.iFrameHeightSize.medium;
                } else {
                    iframe.style.height = variables.iFrameHeightSize.large;
                }
            }
        };

        function setErrorsCounter(value) {
            localStorage.setItem(variables.settings.ERROR_COUNTER_STORAGE_KEY, value);
        };
        function incrementErrorsCounter() {
            var counter = parseInt(localStorage.getItem(variables.settings.ERROR_COUNTER_STORAGE_KEY), 10);
            counter = counter + 1;
            setErrorsCounter(counter.toString());
            return counter;
        };
        function seamlessFormInitErrorHandler(response) {
            console.error(response);
            disableButtonById(variables.button.SUBMIT_ORDER);
            let responseKeys = Object.keys(response);
            let hasMessages = false;
            responseKeys.forEach(
                function ( responseKey ) {
                    if (responseKey.startsWith(variables.settings.WPP_ERROR_PREFIX)) {
                        hasMessages = true;
                        messageList.addErrorMessage({
                            message: response[responseKey]
                        });
                    }
                }
            );
            if (!hasMessages) {
                messageList.addErrorMessage({
                    message: $t("credit_card_form_loading_error")
                });
            }
            if (incrementErrorsCounter() <= variables.settings.MAX_ERROR_REPEAT_COUNT) {
                setTimeout(function () {
                    location.reload();
                }, 3000);
            } else {
                setErrorsCounter("0");
            }
            hideSpinner();
        };
        function seamlessFormSubmitErrorHandler(response) {
            console.error(response);
            hideSpinner();
            let validErrorCodes = variables.settings.WPP_CLIENT_VALIDATION_ERROR_CODES;
            var isClientValidation = false;
            if (response.errors.length > 0) {
                response.errors.forEach(
                    function ( item ) {
                        if (validErrorCodes.includes(item.error.code)) {
                            isClientValidation = true;
                            enableButtonById(variables.button.SUBMIT_ORDER);
                        } else {
                            messageList.addErrorMessage({
                                message: item.error.description
                            });
                        }
                    }
                );
            }
            if (!isClientValidation) {
                disableButtonById(variables.button.SUBMIT_ORDER);
                setTimeout(function () {
                    location.reload();
                }, 3000);
            }
        };

        function seamlessFormSubmitSuccessHandler(response) {
            variables.seamlessResponse = response;
            setErrorsCounter("0");
            this.placeOrder();
        };
        function appendFormData(data, form) {
            for (let key in data) {
                if (key !== "form-url" && key !== "form-method") {
                    form.append($("<input />", {
                        type: "hidden",
                        name: key,
                        value: data[key]
                    }));
                }
            }
        };
        var exports = {
            afterPlaceOrder: function() {
                if (variables.seamlessResponse.hasOwnProperty("acs_url")) {
                    this.redirectCreditCard(variables.seamlessResponse);
                } else {
                    // Handle redirect for Non-3D transactions
                    $.ajax({
                        url: url.build("wirecard_elasticengine/frontend/redirect"),
                        type: "post",
                        data: {
                            "data": variables.seamlessResponse,
                            "method": "creditcard"
                        }
                    }).done(function (data) {
                        // Redirect non-3D credit card payment response
                        window.location.replace(data["redirect-url"]);
                    });
                }
            },
            //todo: substitute block until the next comment with the substitution
            redirectCreditCard: function(response) {
                let result = {};
                result.data = {};
                $.ajax({
                    url: url.build("wirecard_elasticengine/frontend/callback"),
                    dataType: "json",
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
                            message: err
                        });
                    }
                });
            },
            //todo: previus block will be substituted with smth like this
            /* redirectCreditCard: function(response) {
                $.ajax({
                    url: url.build("wirecard_elasticengine/frontend/callback"),
                    dataType: "json",
                    type: "post",
                    data: {"jsresponse": response},
                    success: function (form) {
                        if (form) {
                            form.appendTo("body").submit();
                        }
                    },
                    error: function (err) {
                        messageList.addErrorMessage({
                            message: err
                        });
                    }
                });
            },*/

            placeSeamlessOrder: function(event, divId) {
                showSpinner();
                disableButtonById(variables.button.SUBMIT_ORDER);
                //todo: do we need this event handling?
                if (event) {
                    event.preventDefault();
                }
                WPP.seamlessSubmit({
                    wrappingDivId: divId,
                    onSuccess: seamlessFormSubmitSuccessHandler.bind(this),
                    onError: seamlessFormSubmitErrorHandler.bind(this)
                });
            },

            seamlessFormInit: function() {
                let uiInitData = this.getUiInitData();
                let wrappingDivId = this.getFormId();
                let formSizeHandler = seamlessFormSizeHandler.bind(this);
                let formInitHandler = seamlessFormInitErrorHandler;
                showSpinner();
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
                setTimeout(function(){
                    if (typeof WPP === "undefined") {
                        hideSpinner();
                        disableButtonById(variables.button.SUBMIT_ORDER);
                        messageList.addErrorMessage({
                            message: $t("credit_card_form_loading_error")
                        });
                    }
                }, 1000);
            }

        }
        return exports;
    }
);

