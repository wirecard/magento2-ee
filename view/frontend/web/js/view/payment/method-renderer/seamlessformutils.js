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

    //6211
    function ($, url, $t, messageList, variables) {

        function seamlessFormSizeHandler () {
            setErrorsCounter( variables.localStorage.initValue);
            hideSpinner();
            enableButtonById(variables.button.submitOrder);
            //todo:getFormId has this in it
            let seamlessForm = document.getElementById(this.getFormId());
            window.addEventListener("resize", resizeIFrame);
            if (seamlessForm !== null) {
                resizeIFrame(seamlessForm);
            }
        }
        function showSpinner () {
            $(variables.tag.body).trigger(variables.spinner.start);
        };
        function hideSpinner () {
            $(variables.tag.body).trigger(variables.spinner.stop);
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
                if (iframe.clientWidth > variables.screenWidth.medium) {
                    iframe.style.height = variables.iFrameHeight.small;
                } else if (iframe.clientWidth > variables.screenWidth.small) {
                    iframe.style.height = variables.iFrameHeight.medium;
                } else {
                    iframe.style.height = variables.iFrameHeight.large;
                }
            }
        };

        function setErrorsCounter(value) {
            localStorage.setItem(variables.localStorage.counterKey, value);
        };
        function incrementErrorsCounter() {
            var counter = parseInt(localStorage.getItem(variables.localStorage.counterKey), 10);
            counter = counter + 1;
            setErrorsCounter(counter.toString());
            return counter;
        };
        function seamlessFormInitErrorHandler(response) {
            console.error(response);
            disableButtonById(variables.button.submitOrder);
            let keys = Object.keys(response);
            let hasMessages = false;
            keys.forEach(
                function ( key ) {
                    if (key.startsWith(variables.wpp.errorPrefix)) {
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
            if (incrementErrorsCounter() <= variables.settings.maxErrorRepeatCount) {
                setTimeout(function () {
                    location.reload();
                }, 3000);
            } else {
                setErrorsCounter(variables.localStorage.initValue);
            }
            hideSpinner();
        };
        function seamlessFormSubmitErrorHandler(response) {
            console.error(response);
            hideSpinner();
            let validErrorCodes = variables.wpp.clientValidationErrorCodes;
            var isClientValidation = false;
            if (response.errors.length > 0) {
                response.errors.forEach(
                    function ( item ) {
                        if (validErrorCodes.includes(item.error.code)) {
                            isClientValidation = true;
                            enableButtonById(variables.button.submitOrder);
                        } else {
                            messageList.addErrorMessage({
                                message: item.error.description
                            });
                        }
                    }
                );
            }
            if (!isClientValidation) {
                disableButtonById(variables.button.submitOrder);
                setTimeout(function () {
                    location.reload();
                }, 3000);
            }
        };

        function seamlessFormSubmitSuccessHandler(response) {
            this.seamlessResponse = response;
            setErrorsCounter(variables.localStorage.initValue);
            this.placeOrder();
        };
        function appendFormData(data, form) {
            for (let key in data) {
                if (key !== variables.key.formUrl && key !== variables.key.formMethod) {
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
                if (this.seamlessResponse.hasOwnProperty(variables.key.acsUrl)) {
                    this.redirectCreditCard(this.seamlessResponse);
                } else {
                    // Handle redirect for Non-3D transactions
                    $.ajax({
                        url: url.build("wirecard_elasticengine/frontend/redirect"),
                        type: variables.method.post,
                        data: {
                            "data": this.seamlessResponse,
                            "method": variables.data.value.creditCard
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
                    url: url.build(variables.url.callback),
                    dataType: variables.dataType.json,
                    type: variables.method.post,
                    data: {
                        "jsresponse": response
                    },
                    success: function (result) {
                        if (result.data[variables.key.formUrl]) {
                            let form = $("<form />", {
                                action: result.data[variables.key.formUrl],
                                method: result.data[variables.key.formMethod]
                            });
                            appendFormData(result.data, form);
                            form.appendTo(variables.tag.body).submit();
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
                disableButtonById(variables.button.submitOrder);
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
                let formInitHandler = seamlessFormInitErrorHandler.bind(this);
                showSpinner();
                // wait until WPP-js has been loaded
                $.getScript(this.getPaymentPageScript(), function () {
                    // Build seamless renderform with full transaction data
                    $.ajax({
                        url: url.build(variables.url.creditCard),
                        type: variables.method.post,
                        data: uiInitData,
                        success: function (result) {
                            if (variables.status.ok === result.status) {
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
                    if (typeof WPP === variables.dataType.undefined) {
                        hideSpinner();
                        disableButtonById(variables.button.submitOrder);
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

