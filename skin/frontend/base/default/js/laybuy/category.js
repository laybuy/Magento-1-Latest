jQuery(document).ready(function ($) {
    $('.product-info .price-box').each(function(i, obj) {
        var priceText = $(this).find('.regular-price  .price').text();
        if (!priceText) {
            priceText = $(this).find('.special-price  .price').text();
        }
        if (!priceText) {
            priceText = $(this).find('.price-from .price').text();
        }
        var price = parseFloat(priceText.replace(/[^\d.]/g, ''));
        if (price == NaN) {
            return;
        }
        if (price >= laybuyConfig.minOrderAmount && price <= laybuyConfig.maxOrderAmount) {
            var laybuyInstallments = '<div class="laybuy_payments-installments">Or 6 payments from <b>' + laybuyConfig.currencySymbol + (price / 6).toFixed(2) + '</b> with <img src="' + laybuyConfig.logo + '"></div>';
            $(laybuyInstallments).insertAfter(obj);
        }
    });
});