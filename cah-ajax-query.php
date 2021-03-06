<?php
/**
 * Common - Post Query (AJAX)
 *
 * @package CAHdb
 * @author Mike W. Leavitt
 * @copyright 2017 UCF College of Arts and Humanities
 * @license
 *
 * @wordpress-plugin
 * Plugin Name: Common - Post Query (AJAX)
 * Plugin URI:
 * Description: A plugin that can utilize the built-in WordPress query objects to load posts asynchronously with JavaScript.
 * Version: 1.0.0
 * Author: Mike W. Leavitt
 * Author URI:
 * Text Domain: cah-ajax-query
 * License:
 * Licence URI:
 */

$config_path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'wp-config.php';
if ( !file_exists( $config_path ) )
    $config_path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'wordpress' . DIRECTORY_SEPARATOR . 'wp-config.php';

$load_path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'wp-load.php';
if ( !file_exists( $load_path ) )
    $load_path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'wordpress' . DIRECTORY_SEPARATOR . 'wp-load.php';

require_once( $config_path );
require_once( $load_path );

require_once( 'cah-ajax-query-options.php' );


/**
 * Load plugin-specific frontend CSS.
 *
 * @return void
 */
function cah_ajax_query_load_plugin_css() {

    $adv = get_option( 'CAQ_options_advanced' );

    if ( !empty( $adv['custom_css'] ) ) {

        $cssURL = get_stylesheet_directory_uri() . $adv['custom_css'];

    } else {

        $cssURL = plugin_dir_url( __FILE__ ) . 'css/cah-ajax-query-style.css';
    }

    wp_enqueue_style( 'cah-ajax-query-style', $cssURL );
}
add_action( 'init', 'cah_ajax_query_load_plugin_css' );


/**
 * Load the JavaScript that will drive the AJAX queries on the front-end, and pass the necessary information
 * via wp_localize_script().
 *
 * @return void
 */
function cah_ajax_query_load_scripts() {

    $options = get_option( 'CAQ_options' );
    $adv_options = get_option( 'CAQ_options_advanced');

    $chosen_pages = array();

    foreach( $options as $index => $option ) {

        $chosen_pages[$index] = $option['page_slug'];
    }

    global $post;
    $page_name = $post->post_name;

    if ( in_array( $page_name, $chosen_pages ) ) {

        $index = array_search( $page_name, $chosen_pages );

        wp_enqueue_script( 'cah-ajax-query-js', plugin_dir_url(__FILE__) . 'js/cah-ajax-query.js', array('jquery'), '20170608', true);
        wp_localize_script( 'cah-ajax-query-js', 'cahAjax', array(
                'ajaxURL'               => admin_url( 'admin-ajax.php' ),
                'actionArchive'         => 'cah_ajax_query_retrieve_archive',
                'actionIndex'           => 'cah_ajax_query_retrieve_index',
                'displayAs'             => $options[$index]['display_as'],
                'postType'              => $options[$index]['post_type'],
                'categories'            => json_encode( $options[$index]['categories'] ),
                'persistentCategory'    => ( $options[$index]['persistent_category'] ) ? $options[$index]['persistent_category'] : NULL,
                'postsPerPage'          => $options[$index]['posts_per_page'],
                'resultDivId'           => ( !empty( $adv_options['result_id'] ) ) ? $adv_options['result_id'] : 'cah-ajax-query-container'
        ));
    } // End if

}
add_action( 'wp_enqueue_scripts', 'cah_ajax_query_load_scripts' );


/**
 * This is the core of the Plugin: the actual handler function that will be run by
 * wp-admin/admin-ajax.php in response to the client-side AJAX request.
 *
 * @return string $resp_HTML - The HTML-wrapped output from the query, for client-side handling via JavaScript
 */
add_action( 'wp_ajax_cah_ajax_query_retrieve_archive', 'cah_ajax_query_retrieve_archive' );
add_action( 'wp_ajax_nopriv_cah_ajax_query_retrieve_archive', 'cah_ajax_query_retrieve_archive' );
function cah_ajax_query_retrieve_archive() {

	global $wp_query;

	$resp_HTML = '';

    $adv = get_option( 'CAQ_options_advanced' );

    $item_class     =   ( !empty( $adv['row_class'] ) )     ? $adv['row_class']     : 'article-row';
    $thumb_class    =   ( !empty( $adv['thumb_class'] ) )   ? $adv['thumb_class']    : 'article-thumb';
    $text_class     =   ( !empty( $adv['text_class'] ) )    ? $adv['text_class']     : 'article-text';

	$type                  = ( isset($_POST['type'] )                  && !empty( $_POST['type'] ) )                ? $_POST['type']                        : 'post'; // Defaults to Post
	$display_categories    = ( isset($_POST['categories'] )            && !empty( $_POST['categories'] ) )          ? json_decode( stripslashes( $_POST['categories'] ) )   : NULL;
	$per_page              = ( isset( $_POST['per_page'] )             && !empty($_POST['per_page'] ) )             ? $_POST['per_page']                    : 10; // Defaults to 10
    $persistent_category   = ( isset( $_POST['persistent_category'] )  && !empty( $_POST['persistent_category'] ) ) ? $_POST['persistent_category']         : '';
	$genre                 = ( isset( $_POST['genre'] )                && !empty( $_POST['genre'] ) )               ? $_POST['genre']                       : '';
	$paged                 = ( isset( $_POST['page'] )                 && !empty( $_POST['page'] ) )                ? $_POST['page']                        : 1;

    $query_categories = '';

    if ( !empty( $persistent_category ) ) {

        $query_categories = $persistent_category;

        if ( !empty( $genre ) ) {

            $query_categories .= '+' . $genre;
        }

    } else {

        $query_categories = $genre;
    }

	$args = array(
		'post_type'         => $type,
		'post_status'       => 'publish',
		'posts_per_page'    => $per_page,
	);

    if ( !empty( $query_categories ) )
        $args['category_name'] = $query_categories;

	if ( !empty( $paged ) && $per_page != -1 )
		$args['paged'] = $paged;

	query_posts($args);

	if ( have_posts() ) {
		while ( have_posts() ) {

			the_post();

			$id          = get_the_ID();
			$title       = get_the_title();
			$excerpt     = get_the_excerpt();
			$permalink   = get_the_permalink();
			$pub_date    = get_the_date();
            $author_last    = get_post_meta( $id, 'author1-last', true );
            $author_first   = get_post_meta( $id, 'author1-first', true );
            $other_authors  = get_post_meta( $id, 'other-authors', true );
			$categories  = get_the_category();

            $other_auth_string = '';

            if ( !empty( $other_authors ) ) {

                $other_arr = explode( ',', $other_authors );

                if ( count( $other_arr ) > 1 ) {

                    for ( $i = 0; $i < count( $other_arr); $i++ ) {

                        if ( $i + 1 == count( $other_arr ) )
                            $other_auth_string .= ', and ';
                        else
                            $other_auth_string .= ', ';

                        $other_auth_string .= $other_arr[$i];
                    } // End for
                } else {

                    $other_auth_string .= ' and ' . $other_arr[0];
                } // End if
            } // End if

			$categories_to_show = array();

			if (!empty( $display_categories ) && !empty( $categories ) ) {
				foreach ($categories as $cat) {

					if ( in_array( $cat->slug, $display_categories) && strcasecmp( $cat->slug, $persistent_category ) !== 0 )
						array_push( $categories_to_show, $cat->name);
				} // End foreach
			} else {
                $categories_to_show[0] = 'EMPTINESS';
            }// End if

			// All killer, no filler.
			if ($title == 'Coming Soon!')
				continue;

			if (kdmfi_has_featured_image( 'author-image', $id) && !has_post_thumbnail() )
				$thumbnail = kdmfi_get_featured_image_src( 'author-image', 'small', $id );

			else if ( has_post_thumbnail() )
				$thumbnail = get_the_post_thumbnail_url( $id );

			else
				$thumbnail = get_stylesheet_directory_uri() . '/public/images/empty.png';

			$resp_HTML .= '<div class="' . $item_class . '">';
			$resp_HTML .= '<a href="' . $permalink . '">';
			$resp_HTML .= '<div class="' . $thumb_class . '" style="background-image: url(' . $thumbnail . ');"></div>';
			$resp_HTML .= '<div class="' . $text_class . '">';
			$resp_HTML .= '<h4>' . $title . '</h4>';

            $resp_HTML .= '<p><em>By';
            $resp_HTML .= ( !empty( $author_first ) ) ? ' ' . $author_first : '';
            $resp_HTML .= ' ' . $author_last;
            $resp_HTML .= $other_auth_string;
            $resp_HTML .= '</em></p>';

			$resp_HTML .= '<p>' . substr( $excerpt, 0, 125 ) . '</p>';

            $resp_HTML .= '<p class="cat-pub-data">';

			if ( !empty( $categories_to_show ) ) {
				$cat_out = '';
				foreach ( $categories_to_show as $cat_name ) {

					$cat_out .= $cat_name;

					if ( next( $categories_to_show ) != false )
						$cat_out .= ', ';
				} // End foreach

				$resp_HTML .= '<em>' . $cat_out . '</em>';
			} // End if

            $resp_HTML .= '<span class="pub-data"><em>Published: ' . $pub_date . '</em></span>';
            $resp_HTML .= '</p>'; // End .cat-pub-data

			$resp_HTML .= '</div>'; // End .article-text
			$resp_HTML .= '</a>';
			$resp_HTML .= '</div>'; // End .article-row
		} // End while

		wp_reset_postdata();

        // Pagination and navigation links.
		$resp_HTML .= '<div id="nav-button-row" class="flex-container">';

        // Previous Page button
		if ( get_previous_posts_link() ) {

			$resp_HTML .= '<div id="prev-button" class="flex-item-nav">';
			$resp_HTML .= get_previous_posts_link( '« Prev' );
			$resp_HTML .= '</div>';

		} else {

			$resp_HTML .= '<div id="prev-button" class="flex-item-nav disabled"><p>« Prev</p></div>';
		} // End if

        // Pagination
		$page_links = paginate_links( array(
			'mid_size' 	=> 3,
			'prev_next' => false,
			'type' 		=> 'array'
		) );

		if ( !empty( $page_links ) ) {

			$resp_HTML .= '<div id="pages" class="flex-item-nav">';

			foreach ( $page_links as $link ) {

				$resp_HTML .= $link;
			} // End foreach

			$resp_HTML .='</div>';
		} // End if

        // Next Page button
		if ( get_next_posts_link() ) {

			$resp_HTML .= '<div id="next-button" class="flex-item-nav">';
			$resp_HTML .= get_next_posts_link( 'Next »' );
			$resp_HTML .= '</div>';

		} else {

			$resp_HTML .= '<div id="next-button" class="flex-item-nav disabled"><p>Next »</p></div>';
		} // End if

	} else {

		$resp_HTML .= '<div class="article-row">';
		$resp_HTML .= '<h4>Sorry!</h4>';
		$resp_HTML .= '<p>No posts were found that matched these criteria.</p>';
		$resp_HTML .= '</div>';
	} // End if

	wp_reset_query();

	echo $resp_HTML;

	wp_die();
} // End cah_ajax_query_retrieve_archive


add_action( 'wp_ajax_cah_ajax_query_retrieve_index', 'cah_ajax_query_retrieve_index' );
add_action( 'wp_ajax_nopriv_cah_ajax_query_retrieve_index', 'cah_ajax_query_retrieve_index' );
function cah_ajax_query_retrieve_index() {

    global $wp_query;

    $resp_HTML = '';

    // Post type to display
    $type                  = ( isset($_POST['type'] )                  && !empty( $_POST['type'] ) )                ? $_POST['type']                        : 'post'; // Defaults to Post
    // Categories to display
	$display_categories    = ( isset($_POST['categories'] )            && !empty( $_POST['categories'] ) )          ? json_decode( stripslashes( $_POST['categories'] ) )   : NULL;
    // Posts per page
    $per_page              = ( isset( $_POST['per_page'] )             && !empty($_POST['per_page'] ) )             ? $_POST['per_page']                    : 10; // Defaults to 10
    // A persistent category (i.e., one that will always apply to every query on this particular page)
    $persistent_category   = ( isset( $_POST['persistent_category'] )  && !empty( $_POST['persistent_category'] ) ) ? $_POST['persistent_category']         : '';
    // A genre to filter results, if applicable.
    $genre                 = ( isset( $_POST['genre'] )                && !empty( $_POST['genre'] ) )               ? $_POST['genre']                       : '';
    // For a paged query, the particular page being requested.
    $paged                 = ( isset( $_POST['page'] )                 && !empty( $_POST['page'] ) )                ? $_POST['page']                        : 1;
    // The letter selected, to narrow results.
    $alpha                 = ( isset( $_POST['alpha'] )                && !empty( $_POST['alpha'] ) )               ? $_POST['alpha']                       : '';

    $query_categories = '';

    // Builds the 'category_name' argument for the query.
    if ( !empty( $persistent_category ) ) {

        $query_categories = $persistent_category;

        if ( !empty( $genre ) ) {

            $query_categories .= '+' . $genre;
        }

    } else {

        $query_categories = $genre;
    }

    // Modifies the meta_query array to only pull the selected letter, if applicable.
    if ( empty( $alpha ) ) {

        $query_meta = array(
            'relation' => 'AND',
            array(
                'key' => 'author1-last',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => 'author1-first',
                'compare' => 'EXISTS'
            )
        );

    } else {

        // Matching with RegExp was the best way I found to make sure the query is only pulling
        // the initial letter.
        $alpha_patt = '^' . $alpha;

        $query_meta = array(
            'relation' => 'AND',
            array(
                'key' => 'author1-last',
                'value' => $alpha_patt,
                'compare' => 'REGEXP'
            ),
            array(
                'key' => 'author1-first',
                'compare' => 'EXISTS'
            )
        );
    } // End if

    // The args to pass to the global $wp-query object with query_posts().
	$args = array(
		'post_type'         => $type,
		'post_status'       => 'publish',
		'posts_per_page'    => $per_page,
        'meta_query'        => $query_meta,
        'orderby' => array(
            'author1-last' => 'ASC',
            'author1-first' => 'ASC'
        )
	);

    if ( !empty( $query_categories ) )
        $args['category_name'] = $query_categories;

	if ( !empty( $paged ) && $per_page != -1 )
		$args['paged'] = $paged;

    // This is a quick secondary query, just grabbing relevant Post IDs in order to determine
    // which alphabet letters the user is able to filter by.
    $alpha_args = array(
        'post_type'         => $type,
		'post_status'       => 'publish',
		'posts_per_page'    => -1,
        'meta_query'        => array(
            'relation' => 'AND',
            array(
                'key' => 'author1-last',
                'compare' => 'EXISTS'
            ),
            array(
                'key' => 'author1-first',
                'compare' => 'EXISTS'
            )
        ),
        'orderby' => array(
            'author1-last' => 'ASC',
            'author1-first' => 'ASC'
        ),
        'fields' => 'ids'
    );

    if ( !empty( $query_categories ) )
        $alpha_args['category_name'] = $query_categories;

    $alpha_query = new WP_Query( $alpha_args );

    if ( $alpha_query->have_posts() ) {

        // The letters that end up in this array will be the filterable letters--the ones
        // which have results that begin with them.
        $alpha_results = array();

        foreach( $alpha_query->posts as $id ) {

            $auth_last = get_post_meta( $id, 'author1-last', true );
            $letter = substr( $auth_last, 0, 1);

            if ( !in_array( $letter, $alpha_results ) )
                array_push( $alpha_results, strtoupper( $letter ) );
        } // End foreach
    } // End if

    // Pass our actual arguments to the main $wp_query object
	query_posts($args);


    // The Loop
    if ( have_posts() ) {

        // Building the alphabet filter.
        $resp_HTML .= '<div id="alpha-bar" class="flex-container">';

        // I found out you can iterate through letters, which is neat. You just can't do $i <= 'Z' as the breakout argument, because for some reason
        // it doesn't stop until 'YZ'. So I just had it stop as soon as it's 'AA'. Works fine.
        for( $i = 'A'; $i != 'AA'; $i++ ) {

            $is_there = ( isset( $alpha_results ) && in_array( $i, $alpha_results ) ) ? true : false;

            $resp_HTML .= '<div id="' . $i . '" class="alpha-filter';
            $resp_HTML .= ( $is_there ) ? ' has-results' : '';
            $resp_HTML .= ( strcasecmp( $alpha, $i ) == 0 ) ? ' active-alpha' : '';
            $resp_HTML .= '">';

            // This is all going to be handled with JS on the front-end; the <a> tags are markers that the
            // script can recognize and attach Event Handlers to.
            $resp_HTML .= ( $is_there ) ? '<a href="javascript:;">' : '';

            $resp_HTML .= '<p>' . $i . '</p>';

            $resp_HTML .= ( $is_there ) ? '</a>' : '';

            $resp_HTML .= '</div>';
        } // End for

        $resp_HTML .= '</div>';

        $resp_HTML .= '<ul>';

        while ( have_posts() ) {

            the_post();

            $id             = get_the_ID();
            $title          = get_the_title();
            $excerpt        = get_the_excerpt();
			$permalink      = get_the_permalink();
			$pub_date       = get_the_date();
			$author_last    = get_post_meta( $id, 'author1-last', true );
            $author_first   = get_post_meta( $id, 'author1-first', true );
            $other_authors  = get_post_meta( $id, 'other-authors', true );
			$categories     = get_the_category();

            $resp_HTML .= '<li';
			if ( in_category( 'aquifer' ) )
				$resp_HTML .= ' class="aqf"';
			elseif ( in_category( 'florida-review' ) )
				$resp_HTML .= ' class="tfr"';
			else
				$resp_HTML .= ' class="tfr"';
			$resp_HTML .= '>';

            $resp_HTML .= '<img src="' . plugin_dir_url( __FILE__ );
			if ( in_category( 'aquifer' ) )
				$resp_HTML .= 'images/TFR-Aquifer-Bullet.svg';
			elseif ( in_category( 'florida-review' ) )
				$resp_HTML .= 'images/TFR-Bird-Bullet.svg';
			else
				$resp_HTML .= 'images/TFR-Bird-Bullet.svg';
            $resp_HTML .= '">';

            $resp_HTML .= '<span class="idx-auth">' . $author_last;
            $resp_HTML .= ( !empty( $author_first ) ) ? ', ' . $author_first : '';

            $other_auth_string = '';

            if ( !empty( $other_authors ) ) {

                $other_arr = explode( ',', $other_authors );

                if ( count( $other_arr ) > 1 ) {

                    for ( $i = 0; $i < count( $other_arr); $i++ ) {

                        if ( $i + 1 == count( $other_arr ) )
                            $other_auth_string .= ', and ';
                        else
                            $other_auth_string .= ', ';

                        $other_auth_string .= $other_arr[$i];
                    } // End for
                } else {

                    $other_auth_string .= ' and ' . $other_arr[0];
                } // End if
            } // End if

            $resp_HTML .= $other_auth_string;

            if ( in_category( 'interview' ) )
                $resp_HTML .= ' for <em>TFR</em>';

            $resp_HTML .= '</span>';

            if ( !empty( $title ) )
                $resp_HTML .= ' | <span class="idx-title"><a href="' . $permalink . '"><strong>' . $title . '</strong></a></span>';
            else
                $resp_HTML .= ' | <span class="idx-title"><a href="' . $permalink . '"><strong>NO TITLE for Post ' . $id . '</strong></a></span>';

            $resp_HTML .= ' | <span class="idx-cats"><em>';

            if ( is_array( $categories ) ) {

                $cats = array();
                foreach ( $categories as $category ) {

                    if ( $category->slug == 'aquifer' || $category->slug == 'florida-review' )
                        continue;

                    if ( in_array( $category->slug, $display_categories ) && strcasecmp( $category->slug, $persistent_category ) != 0 )
                        array_push( $cats, $category->slug );
                } // End foreach

                foreach ( $cats as $cat ) {

                    $resp_HTML .= ( $cat == 'non-fiction' ) ? str_replace( ' ' , '-', ucwords( str_replace( '-', ' ', $cat ) ) ) : ucwords( str_replace( '-', ' ', $cat ) );

                    if ( next( $cats ) !== false )
                        $resp_HTML .= ', ';
                } // End foreach
            } elseif ( !empty( $categories ) ) {

                $resp_HTML .= $categories;

            } else {

                $resp_HTML .= 'CATEGORY PARSE ERROR';
            } // if

            $resp_HTML .= '</em></span>';
			$resp_HTML .= ' | <span class="idx-pub-date"><em>Published ';

			// For TFR items, we have to grab the publication date of the associated issue.
			if ( in_category( 'florida-review' ) ) {

				$issue_full = get_post_meta( $id, 'issue', true );
				$iss_tmp = explode( '.', $issue_full );
				$issue_info = array(
					'volume' => $iss_tmp[0],
					'issue' => ( empty( $iss_tmp[1] ) || $iss_tmp[1] == ' ' ) ? '1' : str_replace( ' &amp; ', ' & ', str_replace(',', ' & ', $iss_tmp[1] ) )
				);

				$iss_meta_qry = array(
					'relation' => 'AND',
					array(
						'key' => 'vol-num',
						'value' => $issue_info['volume'],
						'compare' => '='
					),
					array(
						'key' => 'issue-num',
						'value' => $issue_info['issue'],
						'compare' => '='
					)
				);

				$iss_qry_args = array(
					'post_type' => 'issue',
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'meta_query' => $iss_meta_qry,
					'fields' => 'ids'
				);

				$issue = new WP_Query( $iss_qry_args );

				if ( $issue->have_posts() ) {
					$pub_date_raw = get_post_meta( $issue->posts[0], 'pub-date', true );
					$pub_date_obj = maybe_unserialize( $pub_date_raw );

					$pub_date_clean = date_format( $pub_date_obj, 'F j, Y');

					$resp_HTML .= 'in <a href="' . get_the_permalink( $issue->posts[0] ) . '">Vol. ' . $issue_info['volume'] . '.' . $issue_info['issue'] . '</a>, ' . $pub_date_clean;

				} else {

					$resp_HTML .= $pub_date;
				} // if

			} else {

            	$resp_HTML .= $pub_date;
			} // if

			$resp_HTML .= '</em></span>';

            $resp_HTML .= '</li>';

        } // End while

        $resp_HTML .= '</ul>';

        // Reset the base post object, as is only polite.
        wp_reset_postdata();

        // Pagination and navigation links.
		$resp_HTML .= '<div id="nav-button-row" class="flex-container">';

        // Previous Page button
		if ( get_previous_posts_link() ) {

			$resp_HTML .= '<div id="prev-button" class="flex-item-nav">';
			$resp_HTML .= get_previous_posts_link( '« Prev' );
			$resp_HTML .= '</div>';

		} else {

			$resp_HTML .= '<div id="prev-button" class="flex-item-nav disabled"><p>« Prev</p></div>';
		} // End if

        // Pagination
		$page_links = paginate_links( array(
			'mid_size' 	=> 9,
			'prev_next' => false,
			'type' 		=> 'array'
		) );

		if ( !empty( $page_links ) ) {

			$resp_HTML .= '<div id="pages" class="flex-item-nav">';

			foreach ( $page_links as $link ) {

				$resp_HTML .= $link;
			} // End foreach

			$resp_HTML .='</div>';
		} // End if

        // Next Page button
		if ( get_next_posts_link() ) {

			$resp_HTML .= '<div id="next-button" class="flex-item-nav">';
			$resp_HTML .= get_next_posts_link( 'Next »' );
			$resp_HTML .= '</div>';

		} else {

			$resp_HTML .= '<div id="next-button" class="flex-item-nav disabled"><p>Next »</p></div>';
		} // End if

    } else {

        $resp_HTML .= '<div class="article-row">';
        $resp_HTML .= '<h4>Sorry!</h4>';
        $resp_HTML .= '<p>No posts were found that match these criteria.</p>';
        $resp_HTML .= '</div>';
    } // End if

    // Reset the $wp_query object, so the WP gods don't get mad at you.
    wp_reset_query();

    // Send back the response.
    echo $resp_HTML;

    wp_die();
} // End cah_ajax_query_retrieve_index
?>
