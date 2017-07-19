jQuery(document).ready(function() {

    jQuery('#reset-defaults').on({
        click: function() {
            resetDefaults();
        }
    });
});

function resetDefaults() {

    jQuery.ajax({
        url: cahAdminAjax.ajaxURL,
        method: 'POST',
        data: {
            action: cahAdminAjax.actionReset
        }
    })
        .done( function(resp) {
            if ( resp == 'true' ) {

                jQuery('#advanced-options-form input[type="text"]').val('');
            } else {
                alert( "There was a problem... :/" );
            }
        })
        .fail( function(resp) {
            alert("Failed!" + resp);
        });
}
