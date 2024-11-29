jQuery(document).ready(function($) {
    const id = woocommerceData.id;

    checkbox_sac_select();
    
    $(`#woocommerce_${id}_sac_check`).click(function() {
        checkbox_sac_select();
    });

    function checkbox_sac_select() {
        if ($(`#woocommerce_${id}_sac_check`).is(':checked')) {
            $('table tr:last').fadeIn();
            $(`#woocommerce_${id}_sac_textbox`).prop('required', true);
        } else {
            $('table tr:last').fadeOut();
            $(`#woocommerce_${id}_sac_textbox`).prop('required', false);
        }
    }
});
