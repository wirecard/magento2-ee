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
        'jquery',
        'Wirecard_ElasticEngine/js/view/payment/method-renderer/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/modal',
        'mage/translate',
        'ko'
    ],
    function ($, Component, additionalValidators, url, quote, modal, ko) {
        'use strict';
        return Component.extend({
            accountFirstName: '',
            accountLastName: '',
            bankBic: '',
            bankAccountIban: '',
            mandateId: '',
            mandate: false,
            defaults: {
                template: 'Wirecard_ElasticEngine/payment/method-sepa',
                redirectAfterPlaceOrder: false
            },
            /**
             * Get payment method data
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    'po_number': null,
                    'additional_data': {
                        'accountFirstName': this.accountFirstName,
                        'accountLastName': this.accountLastName,
                        'bankBic': this.bankBic,
                        'bankAccountIban': this.bankAccountIban,
                        'mandateId': this.mandateId
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
                var frm = $('#' + this.getCode() + '-form');
                return frm.validation() && frm.validation('isValid');
            },
            beforePlaceOrder: function (data, event) {
                var self = this;
                if (this.validate()) {
                    var sepaMandate = $('#sepaMandate');

                    sepaMandate.modal({
                        title: $.mage.__('SEPA Direct Debit Mandate Form'),
                        responsive: true,
                        innerScroll: true,
                        buttons: [{
                            text: 'Accept',
                            click: function() {
                                self.mandateId = $("input[name=mandateId]", sepaMandate).val();
                                this.closeModal();
                                self.placeOrder();
                            }
                        },
                            {
                                text: 'Close',
                                click: this.closeModal
                            }],
                        opened: function(){
                                var acceptButton = $("footer button:first", sepaMandate.closest('.modal-inner-wrap'));
                            acceptButton.addClass('disabled');
                            var modal = this;
                            $.get(url.build('wirecard_elasticengine/frontend/sepamandate', {})).done(
                                function (response) {
                                    response = response.replace(/%firstname%/g, $("#wirecard_elasticengine_sepa_accountFirstName").val())
                                        .replace(/%lastname%/g, $("#wirecard_elasticengine_sepa_accountLastName").val())
                                        .replace(/%bankAccountIban%/g, $("#wirecard_elasticengine_sepa_bankAccountIban").val());

                                    if(self.hasBankBic()) {
                                    response = response.replace(/%bankBic%/g, $("#wirecard_elasticengine_sepa_bankBic").val());
                                    } else {
                                        response = response.replace(/%bankBic%/g, '');
                                    }
                                    $(modal).html(response);
                                    $('#sepa-accept', modal).on('change', function(event) {
                                        if ($('#sepa-accept', modal).prop("checked")) {
                                            if (acceptButton.hasClass('disabled')) {
                                                acceptButton.removeClass('disabled');
                                            }
                                        } else {
                                            acceptButton.addClass('disabled');
                                        }

                                    });
                                }
                            );
                        }
                    }).modal('openModal');
                }
            },
            afterPlaceOrder: function () {
                $.get(url.build("wirecard_elasticengine/frontend/callback"), function (data) {
                    if (data['form-url']) {
                        var form = $('<form />', {action: data['form-url'], method: data['form-method']});

                        for (var i = 0; i < data['form-fields'].length; i++) {
                            form.append($('<input />', {
                                type: 'hidden',
                                name: data['form-fields'][i]['key'],
                                value: data['form-fields'][i]['value']
                            }));
                        }
                        form.appendTo('body').submit();
                    } else {
                        window.location.replace(data['redirect-url']);
                    }
                });
            }
        });
    }
);
