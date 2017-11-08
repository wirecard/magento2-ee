/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard Central Eastern Europe GmbH
 * (abbreviated to Wirecard CEE) and are explicitly not part of the Wirecard CEE range of
 * products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License Version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard CEE does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard CEE does not guarantee their full
 * functionality neither does Wirecard CEE assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard CEE does not guarantee the full functionality
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
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/modal/modal',
        'ko',
        'Magento_Checkout/js/action/place-order',
        'mage/storage',
        'mage/translate'
    ],
    function ($, Component, additionalValidators, url, quote, modal, ko, placeOrderAction, storage) {
        'use strict';
        return Component.extend({
            accountFirstName: '',
            accountLastName: '',
            bankBic: '',
            bankAccountIban: '',
            mandate: false,
            defaults: {
                template: 'Wirecard_ElasticEngine/payment/method-sepa',
                redirectAfterPlaceOrder: false,
            },
            initialize: function() {
                this._super();
                this.config = window.checkoutConfig.payment[this.getCode()];
            },
            getLogoUrl: function() {
                return this.config.logo_url;
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
                        'bankAccountIban': this.bankAccountIban
                    }
                };
            },
            hasBankBic: function() {
                return this.config.enable_bic;
            },
            validate: function () {
                var frm = $('#' + this.getCode() + '-form');
                return frm.validation() && frm.validation('isValid');
            },
            beforePlaceOrder: function (data, event) {
                if (this.validate()) {
                    var self = this;
                    var sepaMandate = $('#sepaMandate');

                    $.get(url.build('/wirecard_elasticengine/frontend/sepamandate', {})).done(
                        function (response) {
                            response.replace("%firstname%", $("input[name='payment[sepa_accountFirstName]']").val());
                            response.replace("%lastname%", $("input[name='payment[sepa_accountLastName]']").val());
                            response.replace("%bankBic%", $("input[name='payment[sepa_bankBic]']").val());
                            response.replace("%bankAccountIban%", $("input[name='payment[sepa_bankAccountIban]']").val());
                            sepaMandate.append(response);
                        }
                    );

                    var modalOptions = {
                        title: $.mage.__('SEPA-Lastschrift-Mandat'),
                        autoOpen: true,
                        closeText: '',
                        buttons: [{
                            text: 'Accept',
                            class: '',
                            click: function () {
                                this.closeModal();
                                self.placeOrder();
                            }
                        },
                            {
                                text: 'Close',
                                class: '',
                                click: function () {
                                    this.closeModal();
                                }
                            }]
                    };

                    sepaMandate.modal(modalOptions);
                }
            },
            afterPlaceOrder: function () {
                $.get(url.build("/wirecard_elasticengine/frontend/callback"), function (data) {
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
