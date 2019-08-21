(function() {
    var target = false;

    if ("undefined" !== typeof window.Review) {
        target = window.Review;
    } else if ("undefined" !== typeof window.Payment) {
        target = window.Payment;
    }

    if (target) {
        var reviewSave = target.prototype.save;

        target.prototype.save = function () {
            if (payment.currentMethod === window.Laybuy.methodCode) {
                if ('authorize_capture' === window.Laybuy.paymentAction) {
                    checkout.setLoadWaiting('review', true);

                    var errorMessage = 'Couldn\'t initialize LayBuy payment method.';
                    var request = new Ajax.Request(
                        window.Laybuy.saveUrl,
                        {
                            method: 'post',
                            parameters: {},
                            onSuccess: function (transport) {
                                var response = {};

                                try {
                                    response = eval('(' + transport.responseText + ')');
                                } catch (e) {
                                    response = {};
                                }

                                if (response.success && response.redirectUrl) {
                                    location.href = response.redirectUrl;
                                    return false;
                                } else {
                                    alert(response.error_message || errorMessage);
                                }

                                checkout.setLoadWaiting();
                            }.bind(this),
                            onFailure: function () {
                                checkout.setLoadWaiting();
                                alert(errorMessage);
                            }
                        }
                    );
                } else {
                    /* Call original function */
                    reviewSave.apply(this, arguments);
                }
            } else {
                /* Call original function */
                reviewSave.apply(this, arguments);
            }
        };
    }
})();