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
        "Magento_Checkout/js/view/payment/default",
        "mage/url"
    ],
    function ($, Component, url) {
        "use strict";
        return Component.extend({
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-default",
                redirectAfterPlaceOrder: false
            },
            initialize: function() {
                this._super();
                this.config = window.checkoutConfig.payment[this.getCode()];
            },
            getLogoUrl: function() {
                return this.config.logo_url;
            },
            afterPlaceOrder: function () {
                $.get(url.build("wirecard_elasticengine/frontend/callback"), function (result) {
                    if (typeof result === "object" && result.data.hasOwnProperty("redirect-url")) {
                        window.location.replace(result.data["redirect-url"]);
                    } else {
                        let formJquery = $(result);
                        formJquery.appendTo("body").submit();
                    }
                });
            }
        });
    }
);
