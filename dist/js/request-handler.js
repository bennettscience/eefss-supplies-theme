jQuery(document).ready(function () {
    jQuery('#request-item-btn').click(function () {

        console.log('running script')

        let data = {
            'action': 'request_item',
            'post_id': jQuery(this).data('id'),
            'user_id': ajax_object.user_id,
        }

        // TODO: Error handling, please.
        // TODO: Update DOM with success/failure message
        jQuery.ajax({
            url: ajax_object.ajaxurl,
            type: 'post',
            data: data,
            dataType: 'json',
        })
    });
})
