jQuery(document).ready(function($) {
    $('#doku-check-status-btn').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop('disabled', true);
        $('#doku-status-spinner').addClass('is-active');
        $('#doku-status-feedback').html('<span style="color: #666; background: #f7f7f7; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; display: inline-block;">Checking status with DOKU...</span>');
        
        $.ajax({
            url: dokuAdminCheckStatusData.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'doku_check_status',
                order_id: dokuAdminCheckStatusData.order_id,
                security: dokuAdminCheckStatusData.nonce
            },
            success: function(response) {
                $('#doku-status-spinner').removeClass('is-active');
                if (response.success) {
                    $('#doku-status-feedback').html('<span style="color: #46b450; background: #ecf7ed; border: 1px solid #c3e6cb; padding: 6px 12px; border-radius: 4px; display: inline-block;">' + response.data.message + '</span>');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    btn.prop('disabled', false);
                    $('#doku-status-feedback').html('<span style="color: #dc3232; background: #fbeae5; border: 1px solid #f5c6cb; padding: 6px 12px; border-radius: 4px; display: inline-block;">' + response.data.message + '</span>');
                    if (response.data && response.data.should_reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                }
            },
            error: function() {
                $('#doku-status-spinner').removeClass('is-active');
                btn.prop('disabled', false);
                $('#doku-status-feedback').html('<span style="color: #dc3232; background: #fbeae5; border: 1px solid #f5c6cb; padding: 6px 12px; border-radius: 4px; display: inline-block;">Connection error. Please try again.</span>');
            }
        });
    });
});
