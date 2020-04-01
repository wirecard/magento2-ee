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
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/constants",
    ],

    function ($, url, $t, messageList, SeamlessCreditCardConstants) {

        /**
         * Show loading spinner
         * @since 3.1.2
         */
        function showSpinner () {
            $(SeamlessCreditCardConstants.tag.body).trigger(SeamlessCreditCardConstants.spinner.start);
        }

        /**
         * Hide loading spinner
         * @since 3.1.2
         */
        function hideSpinner () {
            $(SeamlessCreditCardConstants.tag.body).trigger(SeamlessCreditCardConstants.spinner.stop);
        }

        /**
         * Disable submit order button
         * @param id
         * @since 3.1.2
         */
        function disableButtonById() {
            document.getElementById(this.getSubmitBtnId()).disabled = true;
        }

        /**
         * Enable submit order button
         * @param id
         * @since 3.1.2
         */
        function enableButtonById() {
            document.getElementById(this.getSubmitBtnId()).disabled = false;
        }

        /**
         * Resize credit card form frame on different screen sizes
         * @param seamlessForm
         * @since 3.1.2
         */
        function resizeIFrame(seamlessForm) {
            let iframe = seamlessForm.firstElementChild;
            if (iframe) {
                if (iframe.clientWidth > SeamlessCreditCardConstants.screenWidth.medium) {
                    iframe.style.height = SeamlessCreditCardConstants.iFrameHeight.small;
                } else if (iframe.clientWidth > SeamlessCreditCardConstants.screenWidth.small) {
                    iframe.style.height = SeamlessCreditCardConstants.iFrameHeight.medium;
                } else {
                    iframe.style.height = SeamlessCreditCardConstants.iFrameHeight.large;
                }
            }
        }

        /**
         * Set local storage for error display counter
         * @param value
         * @since 3.1.2
         */
        function setErrorsCounter(value) {
            localStorage.setItem(SeamlessCreditCardConstants.localStorage.counterKey, value);
        }

        /**
         * Increment the error counter in the local storage
         * returns {Int}
         * @since 3.1.2
         */
        function incrementErrorsCounter() {
            var counter = parseInt(localStorage.getItem(SeamlessCreditCardConstants.localStorage.counterKey), 10);
            counter = counter + 1;
            setErrorsCounter(counter.toString());
            return counter;
        }

        /**
         * Handle frame resize
         * @since 3.1.2
         */
        function seamlessFormSizeHandler () {
            setErrorsCounter( SeamlessCreditCardConstants.localStorage.initValue);
            hideSpinner();
            enableButtonById.call(this);
            let seamlessForm = document.getElementById(this.getFormId());
            window.addEventListener("resize", resizeIFrame);
            if (seamlessForm !== null) {
                resizeIFrame(seamlessForm);
            }
        }

        /**
         * Handle errors on cc form initialization
         * @param response
         * @since 3.1.2
         */
        function seamlessFormInitErrorHandler(response) {
            /* eslint no-console: ["error", { allow: ["error"] }] */
            console.error(response);
            hideSpinner();
            window.scrollTo(0,0);
            disableButtonById.call(this);
            let responseKeys = Object.keys(response);
            let hasMessages = false;
            responseKeys.forEach(
                function ( responseKey ) {
                    if (responseKey.startsWith(SeamlessCreditCardConstants.wpp.errorPrefix)) {
                        hasMessages = true;
                        messageList.addErrorMessage({
                            message: response[responseKey]
                        });
                    }
                }
            );
            if (!hasMessages) {
                messageList.addErrorMessage({
                    message: $t(SeamlessCreditCardConstants.error.creditCardFormLoading)
                });
            }
            if (incrementErrorsCounter() <= SeamlessCreditCardConstants.settings.maxErrorRepeatCount) {
                setTimeout(function () {
                    location.reload();
                }, SeamlessCreditCardConstants.settings.reloadTimeout);
            } else {
                setErrorsCounter(SeamlessCreditCardConstants.localStorage.initValue);
            }
        }

        /**
         * Handle errors on cc form submit
         * @param response
         * @since 3.1.2
         */
        function seamlessFormSubmitErrorHandler(response) {
            /* eslint no-console: ["error", { allow: ["error"] }] */
            console.error(response);
            hideSpinner();
            window.scrollTo(0,0);
            let validErrorCodes = SeamlessCreditCardConstants.wpp.clientValidationErrorCodes;
            var isClientValidation = false;
            response.errors.forEach(
                function ( item ) {
                    if (validErrorCodes.includes(item.error.code)) {
                        isClientValidation = true;
                        enableButtonById.call(this);
                    } else {
                        messageList.addErrorMessage({
                            message: item.error.description
                        });
                    }
                }
            );
            if (!isClientValidation) {
                disableButtonById.call(this);
                setTimeout(function () {
                    location.reload();
                }, SeamlessCreditCardConstants.settings.reloadTimeout);
            }
        }

        /**
         * Handle success on cc form submit
         * @param response
         * @since 3.1.2
         */
        function seamlessFormSubmitSuccessHandler(response) {
            this.seamlessResponse = response;
            setErrorsCounter(SeamlessCreditCardConstants.localStorage.initValue);
            this.placeOrder();
        }

        /**
         * Handle general errors on seamless form operations
         * @param code
         * @since 3.1.2
         */
        function seamlessFormGeneralErrorHandler(code) {
            hideSpinner();
            window.scrollTo(0,0);
            enableButtonById.call(this);
            messageList.addErrorMessage({
                message: $t(code)
            });
        }

        /**
         * Check if payment is of type 3D
         * @param response
         * @since 3.1.2
         */
        function isThreeDPayment(response) {
            if (response.hasOwnProperty(SeamlessCreditCardConstants.key.acsUrl)) {
                return true;
            }
            return false;
        }

        let exportedFunctions = {

            /**
             * Initialize the seamless cc form
             * @since 3.1.2
             */
            seamlessFormInit: function() {
                let uiInitData = this.getUiInitData();
                let wrappingDivId = this.getFormId();
                let formSizeHandler = seamlessFormSizeHandler.bind(this);
                let formErrorHandler = seamlessFormInitErrorHandler.bind(this);
                showSpinner();
                // wait until WPP-js has been loaded
                $.getScript(window.checkoutConfig.payment[SeamlessCreditCardConstants.data.wppTxType].wpp_url, function () {
                    // Build seamless renderform with full transaction data
                    $.ajax({
                        url: url.build(SeamlessCreditCardConstants.routes.creditCardController),
                        type: SeamlessCreditCardConstants.method.post,
                        data: uiInitData,
                        success: function (result) {
                            if (SeamlessCreditCardConstants.successStatus.ok === result.status) {
                                WPP.seamlessRender({
                                    requestData: JSON.parse(result.uiData),
                                    wrappingDivId: wrappingDivId,
                                    onSuccess: formSizeHandler,
                                    onError: formErrorHandler
                                });
                            } else {
                                seamlessFormGeneralErrorHandler.bind(this,SeamlessCreditCardConstants.error.creditCardFormLoading);
                            }
                        },
                        error: function (err) {
                            seamlessFormGeneralErrorHandler.bind(this,SeamlessCreditCardConstants.error.creditCardFormLoading);
                            /* eslint no-console: ["error", { allow: ["error"] }] */
                            console.error("Error : " + JSON.stringify(err));
                        }
                    });
                });
                setTimeout(function(){
                    if (typeof WPP === SeamlessCreditCardConstants.dataType.undefined) {
                        disableButtonById.call(this);
                        seamlessFormGeneralErrorHandler.bind(this,SeamlessCreditCardConstants.error.creditCardFormLoading);
                    }
                }, 1000);
            },

            /**
             * Place the seamless order
             * @param event, creditcardFormId
             * @since 3.1.2
             */
            placeSeamlessOrder: function(event, creditcardFormId) {
                showSpinner();
                disableButtonById.call(this);
                if (event) {
                    event.preventDefault();
                }
                WPP.seamlessSubmit({
                    wrappingDivId: creditcardFormId,
                    onSuccess: seamlessFormSubmitSuccessHandler.bind(this),
                    onError: seamlessFormSubmitErrorHandler.bind(this)
                });
            },

            /**
             * Handle operations after order is placed
             * @since 3.1.2
             */
            afterPlaceOrder: function() {
                if (isThreeDPayment(this.seamlessResponse)) {
                    this.processThreeDPayment(this.seamlessResponse);
                } else {
                    let self = this;
                    $.ajax({
                        url: url.build(SeamlessCreditCardConstants.routes.redirectController),
                        type: SeamlessCreditCardConstants.method.post,
                        data: {
                            "data": self.seamlessResponse,
                            "method": SeamlessCreditCardConstants.data.creditCard
                        }
                    }).done(function (data) {
                        // Redirect non-3D credit card payment response
                        window.location.replace(data[SeamlessCreditCardConstants.key.redirectUrl]);
                    });
                }
            },

            /**
             * Redirect after seamless 3d transaction
             * @param response
             * @since 3.1.2
             */
            processThreeDPayment: function(response) {
                $.ajax({
                    url: url.build(SeamlessCreditCardConstants.routes.callbackController),
                    type: SeamlessCreditCardConstants.method.post,
                    data: {"jsresponse": response},
                    //submit form received from controller on success
                    success: function (form) {
                        if (form) {
                            let formJquery = $(form);
                            formJquery.appendTo("body").submit();
                        }
                    },
                    error: function () {
                        seamlessFormGeneralErrorHandler.bind(this,SeamlessCreditCardConstants.error.creditCardFormSubmitting);
                    }
                });
            }
        };
        return exportedFunctions;
    }
);

