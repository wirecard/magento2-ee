/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */
//todo: will be substituted by Sebs code
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
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard",
                redirectAfterPlaceOrder: false
            },
            settings: {
                formIdSuffix: "_seamless_form",
                formIdTokenSuffix: "_seamless_token_form",
                maxErrorRepeatCount:3, // MAX_ERROR_REPEAT_COUNT
            },
            wpp: {
                errorPrefix: "error_", // WPP_ERROR_PREFIX
                clientValidationErrorCodes: ["FE0001"] // WPP_CLIENT_VALIDATION_ERROR_CODES
            },
            localStorage: {
                initValue: "0",
                counterKey: "errorCounter" // ERROR_COUNTER_STORAGE_KEY
            },
            button: {
                submitOrder: "wirecard_elasticengine_creditcard_submit" // SUBMIT_ORDER
            },
            error: {
                creditCardFormLoading: "credit_card_form_loading_error"
            },
            url: {
                callback: "wirecard_elasticengine/frontend/callback",
                creditCard: "wirecard_elasticengine/frontend/creditcard",
                redirect: "wirecard_elasticengine/frontend/redirect",
                vault: "wirecard_elasticengine/frontend/vault?hash="
            },
            spinner: {
                start: "processStart",
                stop: "processStop"
            },
            key: {
                formUrl: "form-url",
                formMethod: "form-method",
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
            status: {
                ok: "OK"
            },
            data: {
                creditCard: "creditcard"
            },
            tag: {
                body: "body"
            }
        };
    }
);
