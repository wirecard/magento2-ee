/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define(
    [
        "uiComponent",
        "Magento_Checkout/js/model/payment/renderer-list"
    ],
    function (
        Component,
        rendererList
    ) {
        "use strict";
        rendererList.push(
            {
                type: "wirecard_elasticengine_paypal",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_creditcard",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/creditcard"
            },
            {
                type: "wirecard_elasticengine_sepadirectdebit",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/sepa"
            },
            {
                type: "wirecard_elasticengine_sofortbanking",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_ideal",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/ideal"
            },
            {
                type: "wirecard_elasticengine_giropay",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/giropay"
            },
            {
                type: "wirecard_elasticengine_ratepayinvoice",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/ratepay"
            },
            {
                type: "wirecard_elasticengine_ratepayinstall",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/ratepay"
            },
            {
                type: "wirecard_elasticengine_alipayxborder",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_poipia",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            },
            {
                type: "wirecard_elasticengine_paybybankapp",
                component: "Wirecard_ElasticEngine/js/view/payment/method-renderer/default"
            }
        );

        return Component.extend({});
    }
);
