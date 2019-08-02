jQuery(document).ready(function () {

    jQuery('#request-item-btn').click(function () {

        console.log('Running...');

        // Validate the total quantity available before submitting
        let quant = parseInt(jQuery('#quant').val());
        let avail = parseInt(jQuery('#avail-quant').html());

        console.log(avail);

        try {
            if (!quant) {
                jQuery('#response').text(`Please enter a quantity.`);
                throw new Error(`No quantity entered.`);
            } else if(quant > avail) {
                jQuery('#response').text(`Please enter a quantity no greater than ${avail}.`);
                throw new Error(`Please enter a quantity no more than ${avail}.`)
            }

            let data = {
                'action': 'request_item',
                'post_id': jQuery(this).data('id'),
                'quant': quant,
                'user_id': ajax_object.user_id,
            }

            console.table(data);

            // TODO: Error handling, please.

            let response = jQuery.ajax({
                url: ajax_object.ajaxurl,
                type: 'post',
                data: data,
                dataType: 'json',
            })

            response.done(function(resp) {
               jQuery('#response').text(resp.message);
               jQuery('#avail-quant').text(resp.remaining);
            })

            response.fail(function(err) {
                console.log(`There was a problem: ${err}`);
            })
        } catch(e) {
            console.log(e.message);
        }
    });

    jQuery('#teacherContact').on('show.bs.modal', function() {
        let button = jQuery(event.target);
        let recip;
        if(button.data('useremail')) {
            recip = button.data('useremail');
            console.log(`Found ${recip}`)
        } else {
            console.log(`Found nothing`)
            recip = '';
        }

        let modal = jQuery(this);

        modal.find('#input_3_7').val(recip);
    })
})
