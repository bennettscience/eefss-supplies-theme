jQuery(document).ready(function () {

    // Listen for the user to click on the request button
    jQuery('#request-item-btn').click(function () {

        // Show a little message...
        jQuery('#response').html('Processing...');
        jQuery('#request-item-btn').prop("disabled", true);

        // Validate the total quantity available before submitting
        let quant = parseInt(jQuery('#quant').val());
        let avail = parseInt(jQuery('#avail-quant').html());

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

            // After writing to the database...
            response.done(function(resp) {

                // Open a popup to show the order form
                let child = window.open('about:blank', 'Complete Order', 'height=640,width=950,toolbar=yes,location=no');
                let childFunction = document.createElement('script');
                childFunction.innerHTML = `
                    let closeChild = function() {
                    window.opener.location.reload();
                    window.close();
                }`
                child.document.write('<html><head><link type="text/css" href="http://localhost/wp-content/themes/understrap/css/theme.min.css?ver=0.9.5.1563329543" rel="stylesheet" /><head><body>');
                child.document.head.appendChild(childFunction);
                child.document.body.innerHTML = resp.message;
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
        let stringName;
        let postId;
        if(button.data('useremail')) {
            recip = button.data('useremail');
        } else {
            console.log(`Found nothing`)
            recip = '';
        }

        if(button.data('userstring')) {
            stringName = button.data('userstring');
        } else {
            stringName = '';
        }

        if(button.data('postid')) {
            postId = button.data('postid');
        } else {
            postId = '';
        }

        let modal = jQuery(this);

        modal.find('#input_3_7').val(recip);
        modal.find('#input_3_11').val(stringName);
        modal.find('#input_3_13').val(postId);
    })
})