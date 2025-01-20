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
        customExpiryField.on('input', function() {    
            var value = parseFloat(customExpiryField.val());    
            customExpiryField.next('.error-message').remove();    
              
            if (value < 1 || value > 31) {    
                customExpiryField.addClass('error');    
                const errorMessage = $('<span class="error-message">Please set with numeric in range 1-31</span>');  
                errorMessage.css({  
                    'color': '#E1251B',  
                    'font-size': '11px',  
                    'font-weight': 'normal',
                    'margin-top': '8px',  
                    'display': 'block'
                });  
                customExpiryField.after(errorMessage);    
            } else {    
                customExpiryField.removeClass('error');    
            }    
              
            if (value < 1) {    
                customExpiryField.val(1);    
            } else if (value > 31) {    
                customExpiryField.val(31);    
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
