/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define(
    [
        "jquery",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/bicdefault",
        "mage/translate",
        "mage/url"
    ],
    function ($, Component, $t, url) {
        "use strict";
        return Component.extend({
            bankBic: "",
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-giropay",
                redirectAfterPlaceOrder: false
            }
        });
    }
);
