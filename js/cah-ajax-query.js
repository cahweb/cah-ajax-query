var parentElem; // Global variable to hold the pre-existing parent element

jQuery(document).ready(function() {

    // Set up the field we need and the event listeners to trigger the changes.
    setupPage();
    setupEventHandlers();

    // Defaults to "All"
    var allButton = parentElem.find('#all');
    updateSelection(allButton);
    getNewResults(allButton);

});


/**
 * Sets up the page with the correct markup. Finds and sets the parentElem global element to the
 * element lowest on the DOM that is permanent (built when the page first loaded). This will enable
 * us to dynamically attach the event handlers later on.
 *
 * @return  void
 */
function setupPage() {

    // Get the desired result div ID from the cahAjax object we passed in with wp_localize_script().
    var resultDivId = cahAjax.resultDivId;

    // Try to grab that element.
    var containerDiv = jQuery( '#' + resultDivId );

    // If we can't find it...
    if ( !containerDiv.is('div') ) {

        // ...create it...
        containerDiv = jQuery('<div></div>').attr('id', resultDivId);

        // ...find the nearest ancestor element that already exists...
        if ( jQuery('#main') )
            parentElem = jQuery('#main');
        else if ( jQuery('#primary') )
            parentElem = jQuery('#primary');
        else
            parentElem = jQuery('body');

        // ...and add the new <div>.
        parentElem.append(containerDiv);

    } else {

        // If it does exist already, just make it the parentElem, for efficiency's sake.
        parentElem = containerDiv;
    } // End if

    // Create the div that will hold the filter buttons.
    var filterBar = jQuery('<div></div>').attr({
        'class' : 'flex-container',
        'id'    : 'filter-bar'
    });

    // Add it to the container.
    containerDiv.append(filterBar);

    // Add the "ALL" button to the filter bar.
    filterBar.append(
        jQuery('<div></div>').attr({
            'class' : 'flex-item',
            'id'    : 'all'
        })
        .append(
            jQuery('<a />').attr('href', 'javascript:;')
            .append( jQuery('<p />').html('ALL') )
        )
    );

    // Store the persistent category and parse the categories array.
    var persist = cahAjax.persistentCategory;
    var categories = JSON.parse(cahAjax.categories);

    // Iterate through and add filter buttons for all categories that aren't the persistent category (the
    // persistent category will be added to every query on the back-end, so we don't need a filter button).
    for (var i = 0; i < categories.length; i++) {

        if ( categories[i] == persist )
            continue;

        var filterDiv = jQuery('<div></div>').attr({
            'class' : 'flex-item',
            'id'    : categories[i]
        });

        // Strip the hyphens from anything that isn't "Non-Fiction." I'd really like to develop a heuristic for
        // telling when to take out a hyphen and when to leave it in, but I have no idea how I might begin doing
        // something like that.
        var formatCatStr = ( categories[i] != 'non-fiction' ) ? categories[i].replace('-', ' ') : categories[i];

        filterDiv.append(
            jQuery('<a />').attr('href', 'javascript:;')
                .append( jQuery('<p />').html(formatCatStr.toUpperCase()) )
        );

        filterBar.append(filterDiv);
    } // End for

    // Create the <div> that will contain the results of the AJAX queries.
    var resultDiv = jQuery('<div></div>').attr('id', 'results-display');

    // Add it to the container.
    containerDiv.append(resultDiv);

} // End setupPage


/**
 * Sets up the event handlers on the newly created filter button elements.
 *
 * @return  void
 */
function setupEventHandlers() {

    // Grab each button and add a click event that calls the updateSelection() and
    // getNewResults() funcitons.
    parentElem.find('#filter-bar .flex-item').each(function() {

        jQuery(this).on({
            click: function(event) {

                event.preventDefault();
                updateSelection(this);
                getNewResults(this);
            }
        });
    });
}


/**
 * This is for paginated queries. This finds the navigation buttons and adds the appropriate click events
 * to page through the queries. Note that updateSelection() isn't called, because this should be happening
 * without altering the active filter button.
 *
 * @return  void
 */
function setupPageHandlers() {

    // Grab the buttons we need to add events to. We have to use the parentElem that we set
    // when the page loaded, because in order for JS to successfully attach event handlers with
    // jQuery, we have to start from a static, fixed element that has not been added dynamically.

    // I'm honestly not sure why that's the case; I only know that's how it works.
    var buttons = parentElem.find('#next-button, #prev-button, #pages > a, #alpha-bar .has-results');

    if (buttons) {

        buttons.each(function() {

            jQuery(this).on({
                click: function(event) {

                    event.preventDefault();
                    getNewResults(this);
                }
            });
        });
    }

    // Grab the <span> that holds the number of the current page, if it exists.
    var currentSpan = parentElem.find('span.current');

    // If so, make sure the Previous and Next buttons are enabled or disabled, as appropriate.
    if (currentSpan) {

        var newPage = currentSpan.html();
        var prevButton = parentElem.find('#prev-button');
        var nextButton = parentElem.find('#next-button');

        // If currentSpan is the last page, disable the Next Button, otherwise enable it.
        if (currentSpan.is(':last-child')) {

            if (!nextButton.hasClass('disabled'))
                nextButton.addClass('disabled');

        } else {

            if (nextButton.hasClass('disabled'))
                nextButton.removeClass('disabled');
        } // End if

        // If the value of currentSpan is 1 (i.e., it's the first page), disable the Previous button,
        // otherwise enable it.
        if (newPage) {

            if (newPage == 1) {

                if (!prevButton.hasClass('disabled'))
                    prevButton.addClass('disabled');

            } else {

                if (prevButton.hasClass('disabled'))
                    prevButton.removeClass('disabled');
            } // End if
        } // End if
    } // End if
} // End setupPageHandlers


/**
 * Update the appearance of the active button.
 *
 * @param   JS DOM Element  elem    The element that triggered the click event.
 *
 * @return  void
 */
function updateSelection( elem ) {

    // Removes the "active" class from any buttons that have it, and makes the triggering
    // element the active one.
    jQuery('#filter-bar .flex-item').each(function() {

        if ( jQuery(this).hasClass('active') )
            jQuery(this).removeClass('active');
    });

    jQuery(elem).addClass('active');
} // End updateSelection


/**
 * The bread and butter of the page functionality. This actually puts together the AJAX call
 * and handles the response from the server.
 *
 * @param   JS DOM Element  elem    The element that triggered the click event.
 *
 * @return  void
 */
function getNewResults( elem ) {

    // Set the initial variables to null, in case we don't end up setting them.
    var reqGenre = null;
    var reqPage = null;
    var reqAlpha = null;

    // Get the current page we're on, if the results are paginated, and the current selected filter.
    var currentPage = parentElem.find('span.current').html();
    var currentGenre = parentElem.find('#filter-bar .active').attr('id');
    var currentAlpha = parentElem.find('#alpha-bar .active-alpha').attr('id');

    // Get the ID of the triggering element, so we can know what kind of request we're making.
    var id = jQuery(elem).attr('id');

    // The page number links don't have IDs, but they do have the "page-numbers" class, so we can
    // find them that way.
    if ( !id && jQuery(elem).hasClass('page-numbers') ) {
        id = 'page';
    }

    if ( id.length == 1 ) {

        var alpha = id;
        id = 'alpha';
    }

    // The switch that determines how we're going to set our AJAX request variables.
    switch (id) {

        // If we want everything, leave the varibles null.
        case 'all':
            break;

        // If the user has clicked Next, ask for the subsequent page, but don't change the genre.
        case 'next-button':
            reqGenre = ( currentGenre !== 'all' ) ? currentGenre : null;
            reqPage = ++currentPage;
            reqAlpha = ( currentAlpha !== null ) ? currentAlpha : null;
            break;

        // If the user has clicked Previous, as for the preceding page, but don't change the genre.
        case 'prev-button':
            reqGenre = ( currentGenre !== 'all' ) ? currentGenre : null;
            reqPage = --currentPage;
            reqAlpha = ( currentAlpha !== null ) ? currentAlpha : null;
            break;

        // If the user has clicked a page link directly, ask for that page, but don't change the genre.
        case 'page':
            reqGenre = ( currentGenre !== 'all' ) ? currentGenre : null;
            reqPage = jQuery(elem).html();
            reqAlpha = ( currentAlpha !== null ) ? currentAlpha : null;
            break;

        case 'alpha':
            reqGenre = ( currentGenre !== 'all' ) ? currentGenre : null;
            reqAlpha = alpha;
            break;

        // Otherwise, it must be a filter button, so set the requested Genre to whichever
        // button was clicked.
        default:
            reqGenre = id;

    } // End switch (id)

    var doAction;
    if (cahAjax.displayAs == 'archive')
        doAction = cahAjax.actionArchive;
    else if (cahAjax.displayAs == 'index')
        doAction = cahAjax.actionIndex;

    // The AJAX request.
    jQuery.ajax({
        url: cahAjax.ajaxURL,
        method: 'POST',
        data: {
            'action': doAction,
            'type': cahAjax.postType,
            'categories': cahAjax.categories,
            'display_as': cahAjax.displayAs,
            'persistent_category': cahAjax.persistentCategory,
            'per_page': cahAjax.postsPerPage,
            'genre': reqGenre,
            'page': reqPage,
            'alpha': reqAlpha
        }
    })
        .done(function(resp) {

            // The response should be raw HTML, so we can update the contents of the #results-display <div>
            // with whatever the server sends back.
            parentElem.find('#results-display').html(resp);

            // Then make sure we add the event handlers to any paginated buttons, otherwise clicking the links
            // is going to ask for pages that don't exist.
            setupPageHandlers();
        })
        .fail(function(resp) {
            // This is mostly for debug purposes, just in case. Most of the time, even when it messes up, admin-ajax.php will just return 0.
            alert('Failed!\n' + resp);
        });
} // End getNewResults()
