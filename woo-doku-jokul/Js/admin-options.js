jQuery(document).ready(function($) {    
    const id = woocommerceData.id;    
    
    checkbox_sac_select();    
    toggleTimeRangeDropdown();    
    
    $(`#woocommerce_${id}_sac_check`).click(function() {    
        checkbox_sac_select();    
    });    

    $('#woocommerce_doku_gateway_abandoned_cart').change(function() {    
        toggleTimeRangeDropdown();    
    });    
  
    $('#woocommerce_doku_gateway_time_range_abandoned_cart').change(function() {  
        showCustomExpiry();
    });  
    
    function toggleTimeRangeDropdown() {      
        var abandonedCardValue = $('#woocommerce_doku_gateway_abandoned_cart').val();      
  
        if (abandonedCardValue === 'yes') {  
            $('#woocommerce_doku_gateway_time_range_abandoned_cart').closest('tr').show();      
  
            showCustomExpiry();
        } else {      
            $('#woocommerce_doku_gateway_time_range_abandoned_cart').closest('tr').hide();      
            $('#woocommerce_doku_gateway_custom_time_range_abandoned_cart').closest('tr').hide();  
        }      
    }    

    function showCustomExpiry() {    
        const customExpiryField = $('#woocommerce_doku_gateway_custom_time_range_abandoned_cart');    
        customExpiryField.off('input change blur').on('input change blur', function() {    
            var val = customExpiryField.val() ? customExpiryField.val().trim() : '';
            customExpiryField.next('.error-message').remove();
            customExpiryField.removeClass('error');

            if (val === '') {
                return;
            }

            var value = parseFloat(val);
            if (isNaN(value)) {
                customExpiryField.val(1);
            } else if (value > 30) {
                customExpiryField.val(30);
            } else if (value < 1) {
                customExpiryField.val(1);
            }
        });    
      
        var timeRangeValue = $('#woocommerce_doku_gateway_time_range_abandoned_cart').val();      
        if (timeRangeValue === 'Custom') {      
            $('#woocommerce_doku_gateway_custom_time_range_abandoned_cart').closest('tr').show();    
        } else {      
            $('#woocommerce_doku_gateway_custom_time_range_abandoned_cart').closest('tr').hide();     
        }      
    }  

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
