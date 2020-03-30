/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */
/*global WPP*/
define(
    [
        "jquery",
        "mage/url",
        "mage/translate",
        "Magento_Ui/js/model/messageList",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/variables"
    ],

    function ($, url, $t, messageList, variables) {

        /**
         * Show loading spinner
         */
        function showSpinner () {
            $(variables.tag.body).trigger(variables.spinner.start);
        }

        /**
         * Hide loading spinner
         */
        function hideSpinner () {
            $(variables.tag.body).trigger(variables.spinner.stop);
        }

        /**
         * Disable submit order button
         */
        function disableButtonById(id) {
            document.getElementById(id).disabled = true;
        }

        /**
         * Enable submit order button
         */
        function enableButtonById(id) {
            document.getElementById(id).disabled = false;
        }

        /**
         * Resize credit card form frame on different screen sizes
         */
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
        }

        /**
         * Set local storage for error display counter
         */
        function setErrorsCounter(value) {
            localStorage.setItem(variables.localStorage.counterKey, value);
        }

        /**
         * Increment the error counter in the local storage
         * returns {Int}
         */
        function incrementErrorsCounter() {
            var counter = parseInt(localStorage.getItem(variables.localStorage.counterKey), 10);
            counter = counter + 1;
            setErrorsCounter(counter.toString());
            return counter;
        }

        /**
         * Handle frame resize
         */
        function seamlessFormSizeHandler () {
            setErrorsCounter( variables.localStorage.initValue);
            hideSpinner();
            enableButtonById(variables.button.submitOrder);
            let seamlessForm = document.getElementById(this.getFormId());
            window.addEventListener("resize", resizeIFrame);
            if (seamlessForm !== null) {
                resizeIFrame(seamlessForm);
            }
        }

        /**
         * Handle errors on cc form initialization
         */
        function seamlessFormInitErrorHandler(response) {
            console.error(response);
            hideSpinner();
            window.scrollTo(0,0);
            disableButtonById(variables.button.submitOrder);
            let responseKeys = Object.keys(response);
            let hasMessages = false;
            responseKeys.forEach(
                function ( responseKey ) {
                    if (responseKey.startsWith(variables.wpp.errorPrefix)) {
                        hasMessages = true;
                        messageList.addErrorMessage({
                            message: response[parseInt(responseKey)]
                        });
                    }
                }
            );
            if (!hasMessages) {
                messageList.addErrorMessage({
                    message: $t(variables.error.creditCardFormLoading)
                });
            }
            if (incrementErrorsCounter() <= variables.settings.maxErrorRepeatCount) {
                setTimeout(function () {
                    location.reload();
                }, variables.settings.reloadTimeout);
            } else {
                setErrorsCounter(variables.localStorage.initValue);
            }
        }

        /**
         * Handle errors on cc form submit
         */
        function seamlessFormSubmitErrorHandler(response) {
            console.error(response);
            hideSpinner();
            window.scrollTo(0,0);
            let validErrorCodes = variables.wpp.clientValidationErrorCodes;
            var isClientValidation = false;
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
            if (!isClientValidation) {
                disableButtonById(variables.button.submitOrder);
                setTimeout(function () {
                    location.reload();
                }, variables.settings.reloadTimeout);
            }
        }

        /**
         * Handle success on cc form submit
         */
        function seamlessFormSubmitSuccessHandler(response) {
            variables.seamlessResponse = response;
            setErrorsCounter(variables.localStorage.initValue);
            this.placeOrder();
        }

        /**
         * Handle general errors on seamless form operations
         */
        function seamlessFormGeneralErrorHandler(code) {
            hideSpinner();
            window.scrollTo(0,0);
            messageList.addErrorMessage({
                message: $t(code)
            });
        }

        let exportedFunctions = {

            /**
             * Initialize the seamless cc form
             */
            seamlessFormInit: function() {
                console.log("loading-err", $t("credit_card_form_loading_error"));
                console.log("submitting-err", $t("credit_card_form_submitting_error"));
                let uiInitData = this.getUiInitData();
                let wrappingDivId = this.getFormId();
                let formSizeHandler = seamlessFormSizeHandler.bind(this);
                let formInitHandler = seamlessFormInitErrorHandler;
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
                                seamlessFormGeneralErrorHandler(variables.error.creditCardFormLoading);
                            }
                        },
                        error: function (err) {
                            seamlessFormGeneralErrorHandler(variables.error.creditCardFormLoading);
                            console.error("Error : " + JSON.stringify(err));
                        }
                    });
                });
                setTimeout(function(){
                    if (typeof WPP === variables.dataType.undefined) {
                        disableButtonById(variables.button.submitOrder);
                        seamlessFormGeneralErrorHandler(variables.error.creditCardFormLoading);
                    }
                }, 1000);
            },

            /**
             * Place the seamless order
             */
            placeSeamlessOrder: function(event, divId) {
                showSpinner();
                disableButtonById(variables.button.submitOrder);
                if (event) {
                    event.preventDefault();
                }
                WPP.seamlessSubmit({
                    wrappingDivId: divId,
                    onSuccess: seamlessFormSubmitSuccessHandler.bind(this),
                    onError: seamlessFormSubmitErrorHandler.bind(this)
                });
            },

            /**
             * Handle operations after order is placed
             */
            afterPlaceOrder: function() {
                if (variables.seamlessResponse.hasOwnProperty(variables.key.acsUrl)) {
                    this.redirectCreditCard(variables.seamlessResponse);
                } else {
                    // Handle redirect for Non-3D transactions
                    $.ajax({
                        url: url.build(variables.url.redirect),
                        type: variables.method.post,
                        data: {
                            "data": variables.seamlessResponse,
                            "method": variables.data.creditCard
                        }
                    }).done(function (data) {
                        // Redirect non-3D credit card payment response
                        window.location.replace(data[variables.key.redirectUrl]);
                    });
                }
            },

            /**
             * Redirect after seamless 3d transaction
             */
            redirectCreditCard: function(response) {
                $.ajax({
                    url: url.build("wirecard_elasticengine/frontend/callback"),
                    type: "post",
                    data: {"jsresponse": response},
                    success: function (form) {
                        if (form) {
                            let formJquery = $(form);
                            formJquery.appendTo("body").submit();
                        }
                    },
                    error: function () {
                        seamlessFormGeneralErrorHandler(variables.error.creditCardFormSubmitting);
                    }
                });
            }
        };
        return exportedFunctions;
    }
);

