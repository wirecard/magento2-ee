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
        "Wirecard_ElasticEngine/js/view/payment/method-renderer/default",
        "Magento_Checkout/js/model/payment/additional-validators",
        "mage/url",
        "Magento_Checkout/js/model/quote",
        "Magento_Ui/js/modal/modal",
        "mage/translate",
        "ko"
    ],
    function ($, Component, additionalValidators, url, quote, modal, ko) {
        "use strict";
        return Component.extend({
            accountFirstName: "",
            accountLastName: "",
            bankBic: "",
            bankAccountIban: "",
            mandateId: "",
            mandate: false,
            defaults: {
                template: "Wirecard_ElasticEngine/payment/method-sepa",
                redirectAfterPlaceOrder: false
            },
            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    "method": this.getCode(),
                    "po_number": null,
                    "additional_data": {
                        "accountFirstName": this.accountFirstName,
                        "accountLastName": this.accountLastName,
                        "bankBic": this.bankBic,
                        "bankAccountIban": this.bankAccountIban,
                        "mandateId": this.mandateId
                    }
                };
            },
            hasBankBic: function() {
                if(parseInt(this.config.enable_bic)) {
                    return true;
                }
                return false;
            },
            validate: function () {
                var frm = $("#" + this.getCode() + "-form");
                return frm.validation() && frm.validation("isValid");
            },
            beforePlaceOrder: function (data, event) {
                var self = this;
                if (this.validate()) {
                    var sepaMandate = $("#sepaMandate");

                    sepaMandate.modal({
                        title: $.mage.__("sepa_mandate"),
                        responsive: true,
                        innerScroll: true,
                        buttons: [{
                            text: "Accept",
                            click: function() {
                                self.mandateId = $("input[name=mandateId]", sepaMandate).val();
                                this.closeModal();
                                self.placeOrder();
                            }
                        },
                            {
                                text: "Close",
                                click: this.closeModal
                            }],
                        opened: function(){
                                var acceptButton = $("footer button:first", sepaMandate.closest(".modal-inner-wrap"));
                            acceptButton.addClass("disabled");
                            var modal = this;
                            $.get(url.build("wirecard_elasticengine/frontend/sepamandate", {})).done(
                                function (response) {
                                    response = response.replace(/%firstname%/g, $("#wirecard_elasticengine_sepadirectdebit_accountFirstName").val())
                                        .replace(/%lastname%/g, $("#wirecard_elasticengine_sepadirectdebit_accountLastName").val())
                                        .replace(/%bankAccountIban%/g, $("#wirecard_elasticengine_sepadirectdebit_bankAccountIban").val());

                                    if(self.hasBankBic()) {
                                    response = response.replace(/%bankBic%/g, $("#wirecard_elasticengine_sepadirectdebit_bankBic").val());
                                    } else {
                                        response = response.replace(/%bankBic%/g, "");
                                    }
                                    $(modal).html(response);
                                    $("#sepa-accept", modal).on("change", function(event) {
                                        if ($("#sepa-accept", modal).prop("checked")) {
                                            if (acceptButton.hasClass("disabled")) {
                                                acceptButton.removeClass("disabled");
                                            }
                                        } else {
                                            acceptButton.addClass("disabled");
                                        }

                                    });
                                }
                            );
                        }
                    }).modal("openModal");
                }
            }
        });
    }
);
