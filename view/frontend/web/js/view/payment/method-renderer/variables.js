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
        var exports = {
            screenSize: {
                medium: 768,
                small: 460
            },
            iFrameHeightSize: {
                large: "415px",
                medium: "341px",
                small: "267px"
            },
            seamlessResponse: null,
            
            settings : {
                formIdTokenSuffix: "_seamless_token_form",
                formIdSuffix: "_seamless_form",
                ERROR_COUNTER_STORAGE_KEY: "errorCounter",
                WPP_CLIENT_VALIDATION_ERROR_CODES: ["FE0001"],
                WPP_ERROR_PREFIX: "error_",
                MAX_ERROR_REPEAT_COUNT:3
            },

            button : {
                SUBMIT_ORDER: "wirecard_elasticengine_creditcard_submit"
            },
        }
        return exports;
    }
);