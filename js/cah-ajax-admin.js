jQuery(document).ready(function() {

    // Grab the #edit-dialog <div> and turn it into a jQuery UI Dialog box.
    jQuery('#edit-dialog').dialog({
        autoOpen: false,
        height: 400,
        width: 550,
        modal: true,
        buttons: [
            {
                text: 'Update',
                click: function() {
                    updateQuery();
                    jQuery(this).dialog('close');
                }
            },
            {
                text: 'Cancel',
                click: function() {
                    jQuery( this ).dialog( 'close' );
                },
                close: function() {
                    jQuery( '#edit-dialog input[type="text"]' ).val('');
                }
            }
        ]
    });

    // Set the AJAX event handlers on the page, where appropriate.
    setupEventHandlers();
});


/**
 * Sets event hadlers on the Edit and Delete links in the Existing Queries table.
 *
 * @return  void
 */
function setupEventHandlers() {

    // We have to find the elements we're looking for by starting from a static element, since
    // these query table rows have the potential to be dynamically updated. If we just look for
    // the elements directly, jQuery won't be able to add the event handlers to them.

    // I honestly don't know why this is the case, exactly, but this is how we get it to work.

    jQuery('#existing-queries').find('a.edit-query').each(function(){

        jQuery(this).on({
            click: function(event) {
                event.preventDefault();
                editQuery(this);
            }
        });
    });

    jQuery('#existing-queries').find('a.delete-query').each(function() {

        jQuery(this).on({
            click: function(event) {
                event.preventDefault();
                deleteQuery(this);
            }
        })
    });

    // This should set the handler on the Reset Button on the advanced page.
    var resetButton = jQuery('#reset-defaults');

    if (resetButton.is('input[type="button"]') ) {

        resetButton.on({
            click: function( event ) {
                event.preventDefault();
                resetDefaults();
            }
        });
    }
}


/**
 * Populates the fields of the Edit Query dialog box, then shows the dialog so the user can
 * edit the values.
 *
 * @param   JS DOM element  elem    The element that triggered the Edit Query dialog.
 *
 * @return  void
 */
function editQuery( elem ) {

    // The <tr> element that holds the triggering element. We'll strip the index we need from
    // this element's ID. We use that rather than the "ID" table field because a) that field
    // identified in the markup in any way, and b) the RegExp check makes sure we've grabbed
    // an appropriate element, and not just some random <tr> from elsewhere in the DOM.
    var targetRow = jQuery(elem).closest('tr');

    // In case we find more than one. We shouldn't, but you never know.
    targetRow.each(function() {

        // The pattern to be tested. I suppose technically there could be more than two digits,
        // but that would mean the user had over 100 custom queries on their site, and that doesn't
        // strike me as terribly likely. If that becomes an issue, I can make it more flexible.
        var patt = /query(\d{1,2})/;

        // The ID of the <tr> element, to test against the RegExp pattern.
        var testID = jQuery(this).attr('id');

        // Test the pattern.
        testResult = patt.exec(testID);

        // If we get a result, then do stuff.
        if (testResult) {

            // Grab the table headers, so we can parse the values we're going to grab.
            var rowHeads = [];
            jQuery('#existing-queries').find('th').each(function() {

                rowHeads.push(jQuery(this).html());
            });

            // Grab the child elements of the target row, which should be all our values
            // in the same order as the headers.
            var rowElems = targetRow.children();

            // An empty object, which will be what we use to populate the fields and update
            // the query.
            var rowData = {};

            // For each element in the row, add the field name (from the <th> array) and the
            // value to the rowData object. We're using a poor man's iterator, here, because
            // jQuery's .each() function can be a little weird in that regard, and normal for
            // loops don't work the way they're supposed to with groups of jQuery-selected objects.
            var i = 0;
            rowElems.each(function() {

                // We don't want to copy the Actions column, because that funcitonality is front-end only.
                if (rowHeads[i] != 'Actions') {

                    // Special instructions for the categories.
                    if (rowHeads[i] == 'Categories') {
                        var cats = jQuery(this).html();

                        // We need an array of categories, not a comma-delimited string. This is easily fixed.
                        var catsArray = cats.split(', ');

                        // Reformat the categories so they look like slugs. This makes handling the request easier
                        // on the back end.
                        for (var j = 0; j < catsArray.length; j++) {

                            cat = catsArray[j];

                            if ( cat != 'non-fiction' )
                                catReplace = cat.replace(' ', '-');

                            catFinal = catReplace.toLowerCase();

                            catsArray[j] = catFinal;
                        }

                        rowData[rowHeads[i]] = catsArray;

                    // We also want to slug-ify the post type, for similar reasons.
                    } else if (rowHeads[i] == 'Post Type') {

                        rowData[rowHeads[i]] = jQuery(this).html().toLowerCase();

                    // Everything else is fine as-is.
                    } else {
                        rowData[rowHeads[i]] = jQuery(this).html();
                    }
                }

                // Iterate! We don't need to check against i's value like in a standard for loop, since
                // .each() will stop automagically when it gets to the end.
                i++;

            });

            // Populate the input fields within the dialog.
            jQuery('#edit-index').val(rowData['ID']);
            jQuery('#edit-query-name').val(rowData['Query Name']);
            jQuery('#edit-page-slug').val(rowData['Page Slug']);

            jQuery('input[name="edit_display_as"][value="' + rowData['Display As'] + '"]').prop('checked', true);

            jQuery('#edit-post-type').val(rowData['Post Type']);

            if (rowData['Persistent'] != '' )
                jQuery('#edit-persistent').val(rowData['Persistent'].toLowerCase());
            else
                jQuery('#edit-persistent').val('none');

            // Check the appropriate checkboxes.
            jQuery('#edit-category-fieldset').find('input[type="checkbox"]').each(function() {
                if (jQuery.inArray(jQuery(this).val(), rowData['Categories']) != -1) {
                    jQuery(this).prop('checked', true);
                } else {
                    jQuery(this).prop('checked', false);
                }
            });

            // Set the pagination options properly.
            if (rowData['Paginated'] == 'Y')
                jQuery('#edit-paginated').prop('checked', true);
            else
                jQuery('#edit-paginated').prop('checked', false);

            if (rowData['Posts per Page'] != 'N/A')
                jQuery('#edit-posts-per-page').val(rowData['Posts per Page']);
            else
                jQuery('#edit-posts-per-page').val('');

            // The data is all updated, and the dialog is ready to be shown to the user.
            jQuery('#edit-dialog').dialog('open');
        } // End if
    });
}


/**
 * The function that makes the AJAX call to delete a specific query.
 *
 * @param   JS DOM element  elem    The element that triggered the event.
 *
 * @return  void
 */
function deleteQuery( elem ) {

    // We don't care about all the data, but we still need the index.
    var rowToDelete = jQuery( elem ).closest('tr');

        // Same RegExp check as in the editQuery() function.
        var patt = /query(\d{1,2})/;
        var testID = jQuery(rowToDelete).attr('id');

        var testResult = patt.exec(testID);

        if (testResult) {

            // The AJAX call to delete the query. We're using values we passed to the cahAdminAjax object,
            // which we created and populated with wp_localize_script().
            jQuery.ajax({
                url: cahAdminAjax.ajaxURL,
                method: 'POST',
                data: {
                    'action': cahAdminAjax.actionDelete,
                    'delete_index': testResult[1]
                }
            }) // End .ajax()
                .done(function(resp) {

                    // Parse the JSON string into a JS object.
                    var response = JSON.parse(resp);

                    // Update the Existing Queries table to reflect the changes.
                    updateTable(response);
                }); // End .done()
    }
}


/**
 * The function that updates the query via AJAX once the user has made their edits in the Edit Query dialog and
 * clicked "Update".
 *
 * @return  void
 */
function updateQuery() {

    // Creating a blank object that we will use as our substitute for the normal $_POST variable in PHP.
    var values = {};

    // Set the "do_what" field so the update_option() call on the back-end knows what to do.
    values['do_what'] = 'edit';

    // Set the basic input values.
    values['index'] = jQuery('#edit-index').val();
    values['query_name'] = jQuery('#edit-query-name').val();
    values['page_slug'] = jQuery('#edit-page-slug').val();
    values['display_as'] = jQuery('input[name="edit_display_as"]:checked').val();
    values['post_type'] = jQuery('#edit-post-type').val();
    values['persistent_category'] = jQuery('#edit-persistent').val();

    // Build an array of the checked categories and add it to the object.
    var cats = [];
    jQuery('#edit-category-fieldset').find('input[type="checkbox"]').each(function() {
        if (jQuery(this).is(':checked'))
            cats.push(jQuery(this).val());
    });
    values['categories'] = cats;

    // Check the pagination values and update that.
    values['paginated'] = ( jQuery('#edit-paginated').is(':checked') ) ? 'true' : '';
    values['posts_per_page'] = jQuery('#edit-posts-per-page').val();

    // Turn the object into a JSON string.
    valuesJSON = JSON.stringify(values);

    // The AJAX call, using values passed from the cahAdminAjax object we passed with wp_localize_script().
    jQuery.ajax({
        url: cahAdminAjax.ajaxURL,
        method: 'POST',
        data: {
            'action': cahAdminAjax.actionEdit,
            'values': valuesJSON
        }
    })
        .done(function(resp) {

            // We should get a JSON-encoded array back. We parse it and pass it to the updateTable() function.
            var response = JSON.parse(resp);
            updateTable(response);
        })
        .fail(function(resp) {
            console.log("Failure! " + resp);
        });
}


/**
 * This function updates the Existing Queries table to reflect any changes the user makes.
 *
 * @param   JS Object/Array respObj The object/array returned from one of the earlier AJAX calls, with the
 *                                      new, updated data for the table.
 *
 * @return  void
 */
function updateTable( respObj ) {

    // Grab the table, which is the static element we're using as a reference point to add this stuff.
    var queryTable = jQuery('#existing-queries');

    // Clear out the contents, because we're going to rebuild it from scratch.
    queryTable.empty();

    // This includes rebuilding the headers.
    var outHTML = '<tr>';
    outHTML += '<th>ID</th>';
    outHTML += '<th>Query Name</th>';
    outHTML += '<th>Page Slug</th>';
    outHTML += '<th>Display As</th>'
    outHTML += '<th>Post Type</th>';
    outHTML += '<th>Persistent</th>';
    outHTML += '<th>Categories</th>';
    outHTML += '<th>Paginated</th>';
    outHTML += '<Posts per Page,</th>';
    outHTML += '<th>Actions</th>';
    outHTML += '</tr>';

    // Again with the poor man's iterator, because I don't know of a better way to keep track of which
    // element we're dealing with in a jQuery .each() call. This goes through each array in the response
    // object and builds a <tr> for them in order.
    var j = 0;
    jQuery.each( respObj, function(j) {

        outHTML += '<tr id="query' + j + '">';
        outHTML += '<td>' + j + '</td>';
        outHTML += '<td>' + this['query_name'] + '</td>';
        outHTML += '<td>' + this['page_slug'] + '</td>';
        outHTML += '<td>' + this['display_as'] + '</td>';
        outHTML += '<td>' + this['post_type'] + '</td>';
        outHTML += '<td>' + this['persistent_category'] + '</td>';

        outHTML += '<td class="category-list">';

        // Build the list of categories, turning it back into a comma-delimited string from an array.
        if ( this['categories'] ) {
            for ( var i = 0; i < this['categories'].length; i++ ) {

                if ( this['categories'][i] != 'non-fiction')
                    outHTML += this['categories'][i].replace('-', ' ');
                else
                    outHTML += this['categories'][i];

                if ( ( i + 1 ) != this['categories'].length )
                    outHTML += ', ';
            }
        } else {
            outHTML += this['categories'];
        }
        outHTML += '</td>';

        outHTML += '<td>' + ( this['paginated'] == 'true' ? 'Y' : 'N' ) + '</td>';
        outHTML += '<td>' + ( this['posts_per_page'] != '-1' ? this['posts_per_page'] : 'N/A' ) + '</td>';
        outHTML += '<td><a href="javascript:;" class="edit-query">Edit</a> <a href="javascript:;" class="delete-query">Delete</a></td>';
        outHTML += '</tr>';

        // Iterate! Again, we don't have to break the loop, since jQuery will stop on its own once
        // it's reached the end.
        j++
    });

    // Update the query table with the HTML we just built.
    queryTable.html( outHTML );

    // Add the event handlers to the new rows, so that the Edit and Delete links will work properly.
    setupEventHandlers();
}


function resetDefaults() {

    jQuery.ajax({
        url: cahAdminAjax.ajaxURL,
        method: 'POST',
        data: {
            'action': cahAdminAjax.actionReset
        }
    })
        .done(function(resp) {

            if ( resp == 'true' ) {

                jQuery('custom_css').val('');
                jQuery('result_id').val('');
                jQuery('row_class').val('');
                jQuery('thumb_class').val('');
                jQuery('text_class').val('');
            }
        });
}
