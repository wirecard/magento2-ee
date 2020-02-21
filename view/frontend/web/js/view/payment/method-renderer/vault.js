/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento2-ee/blob/master/LICENSE
 */

define([
    "jquery",
    "Magento_Vault/js/view/payment/method-renderer/vault",
    "mage/url"
], function ($, VaultComponent, url) {
    "use strict";

    let Constant = {

    };

    return VaultComponent.extend({
        defaults: {
            template: "Wirecard_ElasticEngine/payment/method-vault",
        },

        /**
         * @returns {exports.initialize}
         */
        initialize: function () {
            this._super();
            window.vault_1 = this;//TODO: remove
            return this;
        },

        getPaymentPageScript: function () {
            return this.wppUrl;
        },

        selectPaymentMethod: function () {
            this._super();

            let formSizeHandler = this.seamlessFormSizeHandler.bind(this);

            let code = this.getId() + '_seamless_token_form';


            let uiInitData = {
                txtype: this.wpp_txtype,
                token: this.getToken(),
            };

            $.getScript(this.getPaymentPageScript(), function () {
                // Build seamless renderform with full transaction data

                $.ajax({
                    url: url.build("wirecard_elasticengine/frontend/creditcard"),
                    type: "post",
                    data: uiInitData,
                    success: function (result) {
                        if ("OK" === result.status) {
                            let uiInitData = JSON.parse(result.uiData);
                            console.log("uiInitData");
                            console.log(uiInitData);
                            WPP.seamlessRender({
                                requestData: uiInitData,
                                wrappingDivId: code,
                                onSuccess: formSizeHandler,
                                onError: function(response) {
                                    console.log("onError");
                                    console.log(response);
                                }
                            });
                        } else {
                            console.log(result);
                            //hideSpinner();
                            //messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                        }
                    },
                    error: function (err) {
                        //hideSpinner();
                        //messageContainer.addErrorMessage({message: $t("credit_card_form_loading_error")});
                        console.error("Error : " + JSON.stringify(err));
                    }
                });
            });
            return true;
        },

        seamlessFormSizeHandler: function () {
            console.log("seamlessFormSizeHandler");
            console.log(this);
            let seamlessForm = document.getElementById(this.getId() + '_seamless_token_form');
            window.addEventListener("resize", this.resizeIFrame.bind(seamlessForm));
            if (seamlessForm !== null && typeof seamlessForm !== "undefined") {
                this.resizeIFrame(seamlessForm);
            }
        },

        resizeIFrame: function (seamlessForm) {
            console.log("resizeIFrame");
            console.log(this);
            console.log("seamlessForm:");
            console.log(seamlessForm);
            let iframe = seamlessForm.firstElementChild;
            console.log("iframe:");
            console.log(iframe);
            if (iframe) {
                if (iframe.clientWidth > 768) {
                    iframe.style.height = "267px";
                } else if (iframe.clientWidth > 460) {
                    iframe.style.height = "341px";
                } else {
                    iframe.style.height = "415px";
                }
            }
        },

        /**
         * Returns state of place order button
         * @returns {Boolean}
         */
        isButtonActive: function () {
            console.log('vault.js:35');
            },

        initObservable: function () {
            var self = this;
            console.log('vault.js:40');


            //self.$selector = $('#' + self.selector);
            this._super();
            console.log(this.getId());

            this.initEventHandlers();

            return this;
        },

        initEventHandlers: function () {
            console.log(this);
            $('#' + this.getId())
                .on('click', this.setPaymentDetails.bind(this));
        },

        setPaymentDetails: function() {
          console.log(this.getId());
        },

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        /**
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },

        getData: function () {
            var result = null;
            $.ajax({
                url: url.build("wirecard_elasticengine/frontend/vault?hash="+this.getToken()),
                type: "GET",
                dataType: "json",
                async: false,
                success: function (data) {
                    result = data;
                }
            });

            return {
                "method": result.method_code,
                "po_number": null,
                "additional_data": {
                    "token_id": result.token_id,
                    "recurring_payment" : true
                }
            };
        },

        placeOrder: function () {
            return this._super();
        }
    });
});
