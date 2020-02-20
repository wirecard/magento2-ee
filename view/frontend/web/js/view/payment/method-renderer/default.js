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
                    if (result.data["form-url"]) {
                        var form = $("<form />", {action: result.data["form-url"], method: result.data["form-method"]});

                        for (var i = 0; i < result.data["form-fields"].length; i++) {
                            form.append($("<input />", {
                                type: "hidden",
                                name: result.data["form-fields"][i]["key"],
                                value: result.data["form-fields"][i]["value"]
                            }));
                        }
                        form.appendTo("body").submit();
                    } else {
                        window.location.replace(result.data["redirect-url"]);
                    }
                });
            }
        });
    }
);
