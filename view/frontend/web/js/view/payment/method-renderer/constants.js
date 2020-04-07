/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define(
    [],
    function () {
        return {
            seamlessResponse: null,

            screenWidth: {
                medium: 768,
                small: 460
            },
            iFrameHeight: {
                large: "415px",
                medium: "341px",
                small: "267px"
            },
            settings: {
                formIdSuffix: "_seamless_form",
                formIdTokenSuffix: "_seamless_token_form",
                maxErrorRepeatCount:3,
                reloadTimeout: 3000
            },
            wpp: {
                errorPrefix: "error_",
                clientValidationErrorCodes: ["FE0001"]
            },
            localStorage: {
                initValue: "0",
                counterKey: "errorCounter"
            },
            button: {
                submitOrder: "wirecard_elasticengine_creditcard_submit",
                submitOrderVaulted: "wirecard_elasticengine_cc_vault_submit"
            },
            error: {
                creditCardFormLoading: "credit_card_form_loading_error",
                creditCardFormSubmitting: "credit_card_form_submitting_error"
            },
            routes: {
                callbackController: "wirecard_elasticengine/frontend/callback",
                creditCardController: "wirecard_elasticengine/frontend/creditcard",
                redirectController: "wirecard_elasticengine/frontend/redirect",
                vaultController: "wirecard_elasticengine/frontend/vault?hash="
            },
            spinner: {
                start: "processStart",
                stop: "processStop"
            },
            key: {
                formUrl: "form-url",
                formMethod: "form-method",
                formFields: "form-fields",
                acsUrl: "acs_url",
                redirectUrl: "redirect-url"
            },
            dataType: {
                json: "json",
                undefined: "undefined"
            },
            method: {
                get: "GET",
                post: "POST"
            },
            successStatus: {
                ok: "OK"
            },
            data: {
                creditCard: "creditcard",
                wppTxType: "wirecard_elasticengine_creditcard"
            },
            tag: {
                body: "body"
            },
            input: {
                type: {
                    hidden: "hidden"
                }
            },
            id: {
                creditCardRadioButton: "wirecard_elasticengine_creditcard",
                sameShippingAndBillingAddress: "billing-address-same-as-shipping-wirecard_elasticengine_creditcard"
            }
        };
    }
);
