jQuery(document).ready(function($) {
    const id = woocommerceData.id; 
    const title = woocommerceData.title; 

    $('.channel-name-format').text(title);

    $(`#woocommerce_${id}_channel_name`).change(function() {
        $('.channel-name-format').text($(this).val());
    });

    let isSubmitCheckDone = false;

    $("button[name='save']").on('click', function(e) {
        if (isSubmitCheckDone) {
            isSubmitCheckDone = false;
            return;
        }

        e.preventDefault();

        const paymentDescription = $(`#woocommerce_${id}_payment_description`).val();
        if (paymentDescription.length > 250) {
            return swal({
                text: 'Text is too long, please reduce the message and ensure that the length of the character is less than 250.',
                buttons: {
                    cancel: 'Cancel',
                },
            });
        } else {
            isSubmitCheckDone = true;
        }

        $("button[name='save']").trigger('click');
    });
});
