/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

/* globals WPP */

define(['jquery', 'domReady!'], function($, doc) {
    'use strict';
    return {
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-creditcard",
                redirectAfterPlaceOrder: false
            },
            screenSize: {
                medium: 768,
                small: 460
            },
            iFrameHeightSize: {
                large: "415px",
                medium: "341px",
                small: "267px"
            },
            settings : {
                ERROR_COUNTER_STORAGE_KEY: "errorCounter",
                WPP_CLIENT_VALIDATION_ERROR_CODES: ["FE0001"],
                WPP_ERROR_PREFIX: "error_",
                MAX_ERROR_REPEAT_COUNT:3
            },
            button : {
                SUBMIT_ORDER: "wirecard_elasticengine_creditcard_submit"
            }
        };
});
