jQuery(document).ready(function ($) {
    var laybuyInstallments = '<div class="laybuy_payments-installments">Or 6 payments from <b>' + laybuyConfig.amount + '</b> with <img src="' + laybuyConfig.logo + '"><a rel="nofollow" href="#" id="laybuy-learn-more-open">More Info</a></div>';
    $(laybuyInstallments).insertAfter(laybuyConfig.priceBlockClass);
    $("#laybuy-learn-more-open").on("click", function() {
        $("#laybuy-modal").show();
    });
    $(".laybuy-popup-modal-content .close").on("click", function() {
        $("#laybuy-modal").hide();
    });
});