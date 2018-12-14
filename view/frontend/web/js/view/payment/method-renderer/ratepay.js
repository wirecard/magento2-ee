/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

define(
    [
        "jquery",
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/default",
        "Wirecard_ElasticEngine/js/validator/min-age-validator",
        "mage/translate",
        "mage/url"
    ],
    function ($, Component, minAgeValidator, $t, url) {
        "use strict";
        return Component.extend({
            termsChecked: false,
            customerData: {},
            customerDob: null,
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-ratepay",
                redirectAfterPlaceOrder: false
            },
            onTermsCheckboxClick: function () {
                $(".actions-toolbar .primary .action").attr("disabled", !this.termsChecked);
                if (this.termsChecked) {
                    $(".actions-toolbar .primary .action").removeClass("disabled");
                } else {
                    $(".actions-toolbar .primary .action").addClass("disabled");
                }
                return true;
            },
            initObservable: function () {
                this._super().observe("customerDob");
                return this;
            },
            initialize: function() {
                this._super();
                this.config = window.checkoutConfig.payment[this.getCode()];
                this.customerData = window.customerData;
                this.customerDob(this.customerData.dob);
                return this;
            },
            getData: function () {
                return {
                    "method": this.getCode(),
                    "po_number": null,
                    "additional_data": {
                        "customerDob": this.customerDob()
                    }
                };
            },
            getRatepayScript: function() {
                return this.config.ratepay_script;
            },
            validate: function () {
                var errorPane = $("#" + this.getCode() + "-dob-error");
                if (!minAgeValidator.validate(this.customerDob())) {
                    errorPane.html($t("You have to be at least 18 years to use this payment method."));
                    errorPane.css("display", "block");
                    return false;
                }
                if (this.config.address_same && $("#billing-address-same-as-shipping-wirecard_elasticengine_ratepayinvoice").is(":checked") === false) {
                    errorPane.html($t("Shipping and billing address need to be same."));
                    errorPane.css("display", "block");
                    return false;
                }
                var form = $("#" + this.getCode() + "-form");
                return $(form).validation() && $(form).validation("isValid");
            },
            afterPlaceOrder: function () {
                $.get(url.build("wirecard_elasticengine/frontend/callback"), function (data) {
                    if (data["form-url"]) {
                        var form = $("<form />", {action: data["form-url"], method: data["form-method"]});

                        for (var i = 0; i < data["form-fields"].length; i++) {
                            form.append($("<input />", {
                                type: "hidden",
                                name: data["form-fields"][i]["key"],
                                value: data["form-fields"][i]["value"]
                            }));
                        }
                        form.appendTo("body").submit();
                    } else {
                        window.location.replace(data["redirect-url"]);
                    }
                });
            }
        });
    }
);
