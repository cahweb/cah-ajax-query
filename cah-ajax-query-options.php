<?php

/**
 * Adds the stylesheets and JavaScript that we need to run the back-end admin pages, including
 * passing the cahAdminAjax global JS object to the script (with information for the AJAX calls
 * that the script will make) via wp_localize_script().
 *
 * @return void
 */
add_action('admin_enqueue_scripts', 'cah_ajax_query_menu_scripts' );
function cah_ajax_query_menu_scripts() {

    global $pagenow;

    if ( !( $pagenow == 'admin.php' ) || !( isset( $_GET['page'] ) && ( $_GET['page'] == 'cah_ajax_query_menu' || $_GET['page'] == 'cah_ajax_query_menu_adv' ) ) )
        return;

    wp_enqueue_style( 'jquery-ui-styles', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
    wp_enqueue_style( 'cah_ajax_query_admin_style', plugin_dir_url( __FILE__ ) . 'css/cah-ajax-admin-style.css' );
    wp_enqueue_script( 'cah_ajax_query_admin-js', plugin_dir_url( __FILE__ ) . 'js/cah-ajax-admin.js', array( 'jquery', 'jquery-effects-core', 'jquery-ui-core', 'jquery-ui-dialog' ), '20170612', true );
    wp_localize_script( 'cah_ajax_query_admin-js', 'cahAdminAjax', array(
        'ajaxURL'       => admin_url( 'admin-ajax.php' ),
        'actionDelete'  => 'cah_ajax_delete_query',
        'actionEdit'    => 'cah_ajax_edit_query',
        'actionReset'   => 'cah_ajax_reset_defaults'
    ));
}


/**
 * Happy fun times with the Settings API!
 *
 * Here we register the plugin's custom settings, both for the standard menu page and the
 * advanced submenu page, including the validation callback function (if necessary/applicable).
 * We also register all the settings sections and fields that we'll eventually call when we
 * build the menu pages in cah_ajax_options_pages().
 *
 * @return void
 */
add_action( 'admin_init', 'cah_ajax_query_settings_init' );
function cah_ajax_query_settings_init() {

    $option_name        = 'CAQ_options';
    $option_name_adv    = 'CAQ_options_advanced';

    register_setting( 'cah_ajax_query_menu', $option_name, 'cah_ajax_query_option_validation' );
    register_setting( 'cah_ajax_query_menu', $option_name_adv );

    // For each settings Section, we provide:
    //      - the unique section name
    //      - the next we want to appear as the section's heading
    //      - the callback function that will provide any descriptive text underneath the
    //       section heading.
    //      - the name of the menu that we register in cah_ajax_options_pages()

    add_settings_section(
        'cah_ajax_section_new_query',
        'Create New Query',
        'cah_ajax_section_new_query_cb',
        'cah_ajax_query_menu'
    );

    // For each field that we need, we provide:
    //      - the unique field name
    //      - the text we want to appear in the field's associated label
    //      - the callback function that will provide HTML to "build" the field
    //      - the name of the menu we register in cah_ajax_options_pages()
    //      - the name of the section with which the field is to be associated
    //      - an array of values passed as arguments to the callback function.

    add_settings_field(
        'cah_ajax_field_new_query_name',
        '* Query Name',
        'cah_ajax_field_new_query_name_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'query_name',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'cah_ajax_field_new_page_slug',
        '* Page Slug',
        'cah_ajax_field_new_page_slug_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'page_slug',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'cah_ajax_field_new_display_as',
        'Display As',
        'cah_ajax_field_new_display_as_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'display_as',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'cah_ajax_field_new_post_type',
        'Post Type',
        'cah_ajax_field_new_post_type_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'post_type',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'cah_ajax_field_new_persistent_category',
        'Persistent Category',
        'cah_ajax_field_new_persistent_category_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'persistent_category',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'cah_ajax_field_new_categories',
        '* Categories to Show',
        'cah_ajax_field_new_categories_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'categories',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'cah_ajax_field_new_paginated',
        'Paginate Results',
        'cah_ajax_field_new_paginated_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'paginated',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    add_settings_field(
        'cah_ajax_field_new_posts_per_page',
        'Posts per Page',
        'cah_ajax_field_new_posts_per_page_cb',
        'cah_ajax_query_menu',
        'cah_ajax_section_new_query',
        array(
            'label_for' => 'posts_per_page',
            'class' => 'form-entry',
            'option_name' => $option_name
        )
    );

    // Settings for the Advanced Options submenu.
    add_settings_section(
        'cah_ajax_section_advanced',
        'Advanced Options',
        'cah_ajax_section_advanced_cb',
        'cah_ajax_query_menu_adv'
    );

    add_settings_field(
        'cah_ajax_field_advanced_css_path',
        'Custom CSS File',
        'cah_ajax_field_advanced_css_path_cb',
        'cah_ajax_query_menu_adv',
        'cah_ajax_section_advanced',
        array(
            'label_for' => 'custom_css',
            'class' => 'form-entry',
            'option_name' => $option_name_adv
        )
    );

    add_settings_field(
        'cah_ajax_field_advanced_result_container_id',
        'Query Result Container ID',
        'cah_ajax_field_advanced_result_container_id_cb',
        'cah_ajax_query_menu_adv',
        'cah_ajax_section_advanced',
        array(
            'label_for' => 'result_id',
            'class' => 'form-entry',
            'option_name' => $option_name_adv
        )
    );

    add_settings_field(
        'cah_ajax_field_advanced_result_row_class',
        'Individual Result Container Class',
        'cah_ajax_field_advanced_result_row_class_cb',
        'cah_ajax_query_menu_adv',
        'cah_ajax_section_advanced',
        array(
            'label_for' => 'row_class',
            'class' => 'form-entry',
            'option_name' => $option_name_adv
        )
    );

    add_settings_field(
        'cah_ajax_field_advanced_result_thumb_class',
        'Thumbnail Image Class',
        'cah_ajax_field_advanced_result_thumb_class_cb',
        'cah_ajax_query_menu_adv',
        'cah_ajax_section_advanced',
        array(
            'label_for' => 'thumb_class',
            'class' => 'form-entry',
            'option_name' => $option_name_adv
        )
    );

    add_settings_field(
        'cah_ajax_field_advanced_result_text_class',
        'Result Body Text Class',
        'cah_ajax_field_advanced_result_text_class_cb',
        'cah_ajax_query_menu_adv',
        'cah_ajax_section_advanced',
        array(
            'label_for' => 'text_class',
            'class' => 'form-entry',
            'option_name' => $option_name_adv
        )
    );
} // End cah_ajax_query_settings_init


/**
 * Builds the "Create New Query" section heading, including the description.
 *
 * @param   array   $args   Values automatically passed from the add_settings_section() function.
 *
 * @return  void
 */
function cah_ajax_section_new_query_cb( $args ) {
    ?>
    <p id="<?= esc_attr( $args['id'] ); ?>">Fill out a new query here.</p>
    <p><span class="tooltip">* Required</span></p>
    <?php
} // End cah_ajax_section_new_cb


/**
 * Builds the "Query Name" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_new_query_name_cb( $args ) {
    ?>
    <input type="hidden" name="<?= $args['option_name'] . '[do_what]' ?>" value="new">
    <input
        id="<?= esc_attr( $args['label_for'] ); ?>"
        name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
        type="text"
        size="30"
        maxlength="30"
        placeholder="My Custom Query"
        required>
    <span class="tooltip"> Name your new query.</span>
    <?
} // End cah_ajax_field_new_query_name_cb


/**
 * Builds the "Page Slug" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_new_page_slug_cb( $args ) {
    ?>
    <input
        id="<?= esc_attr( $args['label_for'] ); ?>"
        name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
        type="text"
        size="30"
        maxlength="30"
        placeholder="my-desired-page"
        required>
    <span class="tooltip"> Type in the slug of the page where you want the new query to appear.</span>
    <?
} // End cah_ajax_field_new_page_slug_cb


function cah_ajax_field_new_display_as_cb( $args ) {
    ?>
    <fieldset>
        <input
            id="display_as_archive"
            name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
            type="radio"
            value="archive"
            checked>
        <label for="display_as_archive">Archive</label>
        <input
            id="display_as_index"
            name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
            type="radio"
            value="index">
        <label for="display_as_index">Index</label>
    </fieldset>
    <span class="tooltip">Choose how you want the posts to be displayed. "Archive" will have larger rows with thumbnail images, sorted by post date. "Index" will have smaller rows and will be sorted by author's name or post title.</span>
    <?
} // End cah_ajax_field_new_display_as_cb


/**
 * Builds the "Post Type" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_new_post_type_cb( $args ) {
    ?>
    <select
        id="<?= esc_attr( $args['label_for'] ); ?>"
        name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>">
    <?

    $pt_args = array(
        'public' => true
    );

    $post_types = get_post_types( $pt_args, 'objects' );
    uasort( $post_types, 'sort_post_types_by_label' ); // See below

    foreach( $post_types as $type ) {
        ?>
        <option id="<?= $type->rewrite['slug'] ?>" value="<?= $type->rewrite['slug'] ?>"><?= $type->label ?></option>
        <?
    } // End foreach
    ?>
    </select>
    <span class="tooltip">Select the Post Type to display.</span>
    <?
} // End cah_ajax_field_new_post_type_cb


/**
 * A quick utility function for alphabetizing things in loops, called above.
 *
 * @param   WP_Post_Type    $pt1    The first post type, which we will compare to the second.
 * @param   WP_Post_Type    $pt2    The second post type, which we will compare to the first.
 *
 * @return  int             anon    The result of the strcasecmp function, for use with uasort(), above.
 */
function sort_post_types_by_label( $pt1, $pt2 ) {

    return strcasecmp(
        $pt1->label,
        $pt2->label
    );
}


/**
 * Builds the "Persistent Category" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_new_persistent_category_cb( $args ) {

    $categories = get_categories( array(
        'orderby' => 'name',
        'order' => 'ASC'
    ));

    ?>
    <select id="<?= esc_attr( $args['label_for'] ); ?>" name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>">
        <option value="none">-- None --</option>
        <?
            foreach( $categories as $category ) {
                ?>
                    <option id="persistent-<?= $category->slug ?>" value="<?= $category->slug ?>"><?= $category->name ?></option>
                <?
            }
        ?>
    </select>
    <span class="tooltip">Select a persistent category that will be shown on the page at all times (this removes the option for the user to sort by this category, as it will always apply).</span>
    <?
} // End cah_ajax_field_new_persistent_category_cb


/**
 * Builds the "Categories to Show" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_new_categories_cb( $args ) {

    $form_categories = get_categories( array(
        'orderby' => 'name',
        'order' => 'ASC'
    ));

    ?>
    <fieldset id="<?= esc_attr( $args['label_for'] ); ?>" class="category-fieldset">

        <?php
        foreach( $form_categories as $form_category ) {
            ?>
            <span>
                <input
                type="checkbox"
                id="<?= $form_category->slug ?>"
                name="<?= $args['option_name'] . '[categories]' . '[' . $form_category->slug . ']' ?>"
                value="<?= $form_category->slug ?>">

                <label for="<?= $form_category->slug ?>"><?= $form_category->name ?></label>
            </span>
            <?php
        } // End foreach
        ?>

    </fieldset>
    <span class="tooltip">Select the categories you want to display. The user will be able to sort by category. At least one category must be selected.</span>
    <?
} // End cah_ajax_field_new_categories_cb


/**
 * Builds the "Paginated" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_new_paginated_cb( $args ) {
    ?>
    <input
        type="checkbox"
        id="<?= esc_attr( $args['label_for'] ); ?>"
        name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
        value="true"
        checked>
    <span class="tooltip"> Decide whether or not to paginate the results.</span>
    <?
} // End cah_ajax_field_new_paginated_cb


/**
 * Builds the "Posts per Page" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_new_posts_per_page_cb( $args ) {
    ?>
    <input
        type="number"
        id="<?= esc_attr( $args['label_for'] ); ?>"
        name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
        placeholder="10">
    <span class="tooltip">Enter the number of posts per page. Defaults to 10. If "Paginated" above is not checked, this value is not read.</span>
    <?
} // End cah_ajax_field_new_posts_per_page_cb


/**
 * Builds the "Advanced Options" section heading, including the description.
 *
 * @param   array   $args   Values automatically passed from the add_settings_section() function.
 *
 * @return  void
 */
function cah_ajax_section_advanced_cb( $args ) {
    ?>
        <p>User Customization Settings</p>
    <?
} // End cah_ajax_section_advanced_cb


/**
 * Builds the "Custom CSS File" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_advanced_css_path_cb( $args ) {

    if ( !isset( $adv_options ) )
        $adv_options = get_option( $args['option_name'] );

    ?>
        <input
            id="<?= esc_attr( $args['label_for'] ); ?>"
            class="<?= esc_attr( $args['class'] ); ?>"
            name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
            type="text"
            size="30"
            maxlength="30"
            placeholder="path/to/custom.css"
            value="<?= ( isset( $adv_options[ $args['label_for'] ] ) ) ? $adv_options[ $args['label_for'] ] : '' ?>"
        >

        <span class="tooltip">The path to a custom CSS file you'd prefer to use, relative to your theme's root directory.</span>
    <?
} // End cah_ajax_field_advanced_css_path_cb


/**
 * Builds the "Query Result Container ID" field (clunky name, I know--I'm working on it). Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_advanced_result_container_id_cb( $args ) {

    if ( !isset( $adv_options ) )
        $adv_options = get_option( $args['option_name'] );

    ?>
        <input
            id="<?= esc_attr( $args['label_for'] ); ?>"
            class="<?= esc_attr( $args['class'] ); ?>"
            name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
            type="text"
            size="30"
            maxlength="30"
            placeholder="cah-ajax-query-container"
            value="<?= ( isset( $adv_options[ $args['label_for'] ] ) ) ? $adv_options[ $args['label_for'] ] : '' ?>"
        >

        <span class="tooltip">The ID of the container that the plugin should place the results in, or will create if not present on the chosen page. Defaults to "cah-ajax-query-container".</span>
    <?
} // End cah_ajax_field_advanced_result_container_id_cb


/**
 * Builds the "Individual Result Container Class" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_advanced_result_row_class_cb( $args ){

    if ( !isset( $adv_options ) )
        $adv_options = get_option( $args['option_name'] );

    ?>
        <input
            id="<?= esc_attr( $args['label_for'] ); ?>"
            class="<?= esc_attr( $args['class'] ); ?>"
            name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
            type="text"
            size="30"
            maxlength="30"
            placeholder="article-row"
            value="<?= ( isset( $adv_options[ $args['label_for'] ] ) ) ? $adv_options[ $args['label_for'] ] : '' ?>"
        >

        <span class="tooltip">The class governing the container of an indvidiual item. Defaults to "article-row".</span>
    <?
} // End cah_ajax_field_advanced_result_row_class_cb


/**
 * Builds the "Thumbnail Image Class" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_advanced_result_thumb_class_cb( $args ) {

    if ( !isset( $adv_options ) )
        $adv_options = get_option( $args['option_name'] );

    ?>
        <input
            id="<?= esc_attr( $args['label_for'] ); ?>"
            class="<?= esc_attr( $args['class'] ); ?>"
            name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
            type="text"
            size="30"
            maxlength="30"
            placeholder="article-thumb"
            value="<?= ( isset( $adv_options[ $args['label_for'] ] ) ) ? $adv_options[ $args['label_for'] ] : '' ?>"
        >

        <span class="tooltip">The class assigned to the Featured Image (or author image, if available). Defaults to "article-thumb".</span>
    <?
} // End cah_ajax_field_advanced_result_thumb_class_cb


/**
 * Builds the "Result Body Text Class" field. Using the $args values is useful because it allows us to set and/or change
 * the various IDs and associated values in the $_POST variable by only editing the add_settings_field() function.
 * The $args['option_name'] is the name of the option we're modifying with this form, which is necessary for the
 * Settings API to update the option properly as an array.
 *
 * @param   array   $args   Array we defined as an argument for the add_settings_field() function.
 *
 * @return  void
 */
function cah_ajax_field_advanced_result_text_class_cb( $args ) {

    if ( !isset( $adv_options ) )
        $adv_options = get_option( $args['option_name'] );

    ?>
        <input
            id="<?= esc_attr( $args['label_for'] ); ?>"
            class="<?= esc_attr( $args['class'] ); ?>"
            name="<?= $args['option_name'] . '[' . $args['label_for'] . ']' ?>"
            type="text"
            size="30"
            maxlength="30"
            placeholder="article-text"
            value="<?= ( isset( $adv_options[ $args['label_for'] ] ) ) ? $adv_options[ $args['label_for'] ] : '' ?>"
        >

        <span class="tooltip">The class assigned to the text within the result's description. Defaults to "article-text".</span>
    <?
} // End cah_ajax_field_advanced_result_text_class_cb


/**
 * Registers the plugin menu and the Advanced submenu page.
 *
 * @return void
 */
add_action( 'admin_menu', 'cah_ajax_options_pages' );
function cah_ajax_options_pages() {

    // This registers a new top-level menu on the Dashboard. We provide:
    //      - The contents of the <title> tag in the header for the menu page
    //      - The text that appears on the admin menu bar
    //      - The capabilities that the user needs to have to see this option on their Dashboard
    //      - the unique name of the menu, referenced by any of the other Settings API functions for
    //       that menu page
    //      - the callback function that will build the HTML for the page

    add_menu_page(
        'CAH Ajax Query Options',
        'CAH Post Query (AJAX)',
        'manage_options',
        'cah_ajax_query_menu',
        'cah_ajax_query_menu_html'
    );

    // Similar to above, only we're adding the submenu. We provide:
    //      - the name of the parent menu
    //      - the contents of the <title> tag in the page header
    //      - the text that appears in the submenu section of the Dashboard
    //      - the capabilities that the user needs to see this option on their Dashboard
    //      - the unique name of this submenu page
    //      - the callback function that will build the HTML for the page

    add_submenu_page(
        'cah_ajax_query_menu',
        'CAH Ajax Query Advanced Options',
        'Advanced',
        'manage_options',
        'cah_ajax_query_menu_adv',
        'cah_ajax_query_menu_adv_html'
    );
}


/**
 * The function that builds the raw HTML for the main settings page. We get a little fancier here
 * than on the Advanced page, because we want to build the table of existing options, so the user
 * can see and/or edit them.
 *
 * @return void
 */
function cah_ajax_query_menu_html() {



    if ( !current_user_can( 'manage_options' ) )
        return;

    // Throw up an Update notification when the user has successfully updated the queries.
    if ( isset( $_GET['settings-updated'] ) )
        add_settings_error( 'cah_ajax_messages', 'cah_ajax_new_query_added', 'Query Added.', 'updated' );

    settings_errors( 'cah_ajax_messages' );

    // We're going to get to invoking the Settings API, but first we're going to make a table.
    ?>
        <div class="wrap">

            <div class="table-container">

                <table id="existing-queries">
                    <tr>
                        <th>ID</th>
                        <th>Query Name</th>
                        <th>Page Slug</th>
                        <th>Display As</th>
                        <th>Post Type</th>
                        <th>Persistent</th>
                        <th>Categories</th>
                        <th>Paginated</th>
                        <th>Posts per Page</th>
                        <th>Actions</th>
                    </tr>
    <?

    // Grab the array of queries.
    $options = get_option( 'CAQ_options' );

    // Iterate through and put them in the table, or display a "nothing to display" message.
    if ( !empty( $options ) ) {

        foreach( $options as $index => $option ) {

            $cats = '';
            foreach( $option['categories'] as $cat) {

                // This is super pedantic, but here, if the category is "Non-Fiction", I remove
                // the hyphen from the slug, capitalize the individual words (because "Fiction"
                // wouldn't be capitalized if the hyphen were still there), and then put the
                // hyphen *back in*. This is extremely specialized, and might not be worth including
                // in the final build, unless we could come up with some kind of library or heuristic
                // for categories that should remain hyphenated.
                if ( $cat == 'non-fiction' )
                    $cats .= str_replace( ' ', '-', ucwords( str_replace( '-', ' ', $cat ) ) );
                else
                    $cats .= ucwords( str_replace('-', ' ', $cat ) );

                if ( next( $option['categories'] ) !== false )
                        $cats .= ', ';
            } // End foreach

            ?>
                    <tr id="query<?= esc_attr( $index ); ?>">
                        <td><?= $index ?></td>
                        <td><?= $option['query_name'] ?></td>
                        <td><?= $option['page_slug'] ?></td>
                        <td><?= $option['display_as'] ?></td>
                        <td><?= ucfirst( $option['post_type'] ); ?></td>
                        <td><?= ucfirst( $option['persistent_category'] ); ?></td>
                        <td class="category-list"><?= $cats ?></td>
                        <td><?= ( $option['paginated'] == 'true' ) ? 'Y' : 'N' ?></td>
                        <td><?= ( $option['posts_per_page'] != '-1' ) ? $option['posts_per_page'] : 'N/A' ?></td>
                        <td><a href="javascript:;" class="edit-query">Edit</a>&nbsp;<a href="javascript:;" class="delete-query">Delete</a></td>
                    </tr>
            <?
        } // End foreach

    } else {
            ?>
                    <tr>
                        <td colspan="9" stlye="text-align: center;">Sorry, there were no queries to display.</td>
                    </tr>
            <?
    } // End if
        ?>
                </table>
            </div>

            <div class="form-div">

                <form id="create-query-form" action="options.php" method="POST">
                <?
                    // Here we actually call the Settings API functions to build the fields we registered in the beginning.
                    settings_fields( 'cah_ajax_query_menu' );

                    do_settings_sections( 'cah_ajax_query_menu' );

                    submit_button( 'Create Query' );


                    // After this, we build the Edit Query HTML, which will be wrapped up and hidden with the JQuery UI Dialog function
                    // on the front-end. This form is almost identical to the Create Query form, but behaves a little differently (obviously).
                ?>
                </form>
            </div>
        </div>

        <div id="edit-dialog" title="Edit Query">
            <fieldset>
                <input type="hidden" id="edit-index" name="index">

                <label for="edit-query-name">Query Name: </label>
                <input type="text" id="edit-query-name">
                <br /><br />

                <label for="edit-page-slug">Page Slug: </label>
                <input type="text" id="edit-page-slug">
                <br /><br />

                <fieldset id="edit-display-as">
                    <legend>Display As: </legend>

                    <input id="edit-display-archive" type="radio" name="edit_display_as" value="archive">
                    <label for="edit-archive">Archive</label>

                    <input id="edit-display-index" type="radio" name="edit_display_as" value="index">
                    <label for="edit-display-index">Index</label>
                </fieldset>

                <br />

                <label for="edit-post-type">Post Type: </label>
                <select id="edit-post-type">
                <?
                $pt_args = array(
                    'public' => true,
                );
                $post_types = get_post_types( $pt_args, 'objects');

                foreach( $post_types as $type ) {

                ?>
                    <option id="edit-type-<?= esc_attr( $type->rewrite['slug'] ); ?>" value="<?= $type->rewrite['slug'] ?>"><?= $type->label ?></option>
                <?
                } // End foreach
                ?>
                </select>

                <br /><br />

                <label for="edit-persistent">Persistent Category: </label>
                <select id="edit-persistent">
                    <option value="none" selected> -- None -- </option>
                <?
                $categories = get_categories( array(
                    'orderby'   => 'name',
                    'order'     => 'ASC'
                ));

                foreach ( $categories as $category ) {
                ?>
                    <option value="<?= $category->slug ?>"><?= $category->name ?></option>
                <?
                } // End foreach
                ?>
                </select>
                <br /><br />

                <fieldset id="edit-category-fieldset" class="category-fieldset">
                    <legend>Categories: </legend>
                <?
                foreach( $categories as $category ) {
                ?>
                    <span>
                        <input type="checkbox" id="<?= $category->slug ?>" value="<?= $category->slug ?>">
                        <label for="<?= $category->slug ?>"><?=$category->name ?></label>
                    </span>
                <?
                } // End foreach
                ?>

                </fieldset>
                <br />

                <label for="edit-paginated">Paginated: </label>
                <input type="checkbox" id="edit-paginated" value="true">
                <br /><br />

                <label for="edit-posts-per-page">Posts per Page: </label>
                <input type="number" id="edit-posts-per-page" placeholder="10">
            </fieldset>
        </div>
    <?
} // End cah_ajax_query_menu_html


/**
 * This is the validation callback function that we specified in register_setting(), above. It is called
 * every time the user submits the Create New Query form, and also every time update_option is called for
 * the registered setting (in this case, CAQ_options).
 *
 * It calls one of a few secondary helper functions depending upon the value of the 'do_what' field in the
 * $_REQUEST variable, and sends the result back to WordPress to be serialized and updated.
 *
 * @param   array   $values         The contents of the $_REQUEST variable, with the same field names.
 *
 * @return  array   $new_options    The updated and appropriately formatted options array, for serialization
 *                                      and updating in the WP database.
 */
function cah_ajax_query_option_validation( $values ) {

    // Create an empty array to receive the results of whichever helper function is called.
    $new_options = array();

    // A switch on the value of the "do_what" field, so we can pass the data to the appropriate
    // function.
    switch( $values['do_what'] ) {

        // If it's a new query
        case 'new':
            $new_options = cah_ajax_add_valid_query( $values );
            break;

        // If we're deleting a query
        case 'delete':
            $new_options = cah_ajax_delete_valid_query( $values );
            break;

        // If we're editing a query
        case 'edit':
            $new_options = cah_ajax_edit_valid_query( $values );
            break;

        // If, for some reason, we're not doing any of those things, change nothing.
        default:
            $new_options = get_option( 'CAQ_options' );
            break;
    }

    // Pass back the results back to WordPress, to be serialized and added/updated to the wp_options table.
    return $new_options;

}


/**
 * This formats and adds a new query. This should be called if the incoming values are from the
 * "Create New Query" form on the main options page.
 *
 * @param   array   $values             This is the identical array from the validation function, above.
 *
 * @return  array   $current_options    The complete new options array to be updated.
 */
function cah_ajax_add_valid_query( $values ) {

    // Get the current values, so we can add to them.
    $current_options = get_option( 'CAQ_options' );

    // Just in case something is really, really wrong in the $_REQUEST. This will
    // keep the options from updating if there's a problem.
    if ( !is_array( $values ) )
        return $current_options;

    // Empty array to serve as the query we'll be adding to $current_options, at the end.
    $new_query = array();

    // Update basic values that don't need reformatting.
    $new_query['query_name'] = $values['query_name'];
    $new_query['page_slug'] = $values['page_slug'];
    $new_query['post_type'] = $values['post_type'];
    $new_query['display_as'] = $values['display_as'];

    // Reformat the stuff that needs it.

    if ( $values['persistent_category'] == 'none' )
        $new_query['persistent_category'] == '';
    else
        $new_query['persistent_category'] = $values['persistent_category'];

    if ( !isset( $values['paginated'] ) ) {

        $new_query['paginated'] = '';
        $new_query['posts_per_page'] = '-1';

    } else {

        $new_query['paginated'] = 'true';

        if ( isset( $values['posts_per_page'] ) )
            $new_query['posts_per_page'] = (string) $values['posts_per_page'];
        else
            $new_query['posts_per_page'] = '10';
    }

    // This step may actually be unnecessary at this point, but I had problems with
    // getting the values to transfer over properly before, so I just decided to
    // create a second array.
    $cats_out = array();

    foreach ( $values['categories'] as $category) {

        array_push( $cats_out, $category );
    }

    $new_query['categories'] = $cats_out;

    // If this is the first query we're adding to the options, turn it into an array.
    if ( !is_array( $current_options ) )
        $current_options = array();

    // Add the new query to the array.
    array_push( $current_options, $new_query );

    // The updated options array, ready to be serialized and updated.
    return $current_options;
}


/**
 * This deletes a query. A simpler process, to be sure; all we really need is the index of the
 * query that we want to axe. This should be called as a result of the update_option() call in
 * the cah_ajax_delete_query() AJAX function, below.
 *
 * @param   array   $values         The same array from the validation function, above. In this case,
 *                                      it should have just a "do_what" field and an "index" field.
 *
 * @return  array   $new_options    The new options array, with the indices rearranged appropriately.
 */
function cah_ajax_delete_valid_query( $values ) {

    // Get the current option value.
    $options = get_option( 'CAQ_options' );

    // Store the index we want to delete.
    $del_index = $values['index'];

    // Unset the option at that index, and rearrange the array so that the empty index is deleted and
    // the rest of the options are moved to fill the empty slot. Right now the plugin never provides a
    // hard index reference, so this shouldn't break anything, and it will keep the options array from
    // filling up with empty array elements as queries are deleted and added.
    unset( $options[ $del_index ] );
    $new_options = array_values( $options );

    // Return the new options array, less the index we wanted to delete.
    return $new_options;
}


/**
 * The function called to edit the values of an existing query. This should be called as a result of
 * the update_option() call in the cah_ajax_edit_query() function, below.
 *
 * @param   array   $values             The same array from the validation function, above.
 *
 * @return  array   $current_options    The updated options array, ready to be handed back to WP.
 */
function cah_ajax_edit_valid_query( $values ) {

    // Get the current options array.
    $current_options = get_option( 'CAQ_options' );

    // The index of the array we want to edit.
    $edit_index = $values['index'];

    // The $values array should be organized very similarly to the arrays in $current_options already,
    // so all we need to do is remove the "index" and "do_what" fields, which we do with a custom
    // array_filter() call, telling it to use the array keys rather than the values. We do this rather than
    // something like unset() because unset() will just make the fields NULL, and will not remove them
    // from the array.
    $new_array = array_filter( $values, 'cah_ajax_edit_array_filter_cb', ARRAY_FILTER_USE_KEY );

    if ( $values['persistent_category'] == 'none' )
        $new_array['persistent_category'] = '';

    // Overwrite the old query with the new values.
    $current_options[$edit_index] = $new_array;

    // Return the updated options, for serialization and addition to the WPDB.
    return $current_options;
}


/**
 * Custom filter function, called in array_filter(), above. Gets rid of the "index" and
 * "do_what" fields, which we don't need to be stored. If the function returns false, the
 * tested key will be removed from the array.
 *
 * @param   string  $key    The current key to be tested.
 *
 * @return  boolean         Whether or not to keep the key-value pair in the array.
 */
function cah_ajax_edit_array_filter_cb( $key ) {

    // The fields we want to keep.
    $filter = array(
        'query_name',
        'page_slug',
        'display_as',
        'post_type',
        'persistent_category',
        'paginated',
        'posts_per_page',
        'categories'
    );

    // If the $key matches one of them, keep it. If not, get rid of it.
    if ( in_array( $key, $filter ) )
        return true;
    else
        return false;

}


/**
 * The function that builds the Advanced options submenu page.
 *
 * @return  void
 */
function cah_ajax_query_menu_adv_html() {

    // If you don't have the capabilities to be here, GTFO
    if ( !current_user_can( 'manage_options' ) )
        return;

    // Add the updated message, if the values have just been updated.
    if ( isset( $_GET['settings-updated'] ) ) {

        add_settings_error( 'cah_ajax_messages', 'cah_ajax_message_update', 'Settings Saved.', 'updated' );
    }

    settings_errors( 'cah_ajax_messages' );

    // Build the basic container, and provide a warning to the user that changing these values
    // can screw stuff up on the front end--because it totally can. I normally wouldn't include
    // something like this, but I want the end-user to be able to customize the display and
    // appearance of the query results, so I'm putting this in there.

    // If all else fails, I included a Reset button, so they can put everything back where it
    // was if they break something.
    ?>

        <div class="wrap">
            <div class="notice notice-warning"><p>Changing settings here will affect the operation of the Plugin, and can cause things to break if not set correctly. Please make sure you know what you're changing before you change it.</p></div>

            <form id="advanced-options-form" action="options.php" method="POST">

                <!-- <input type="hidden" name="action" value="cah_ajax_query_advanced_update"> -->

                <?php

                // Call the Settings API to build the form fields.

                settings_fields( 'cah_ajax_query_menu_adv' );

                do_settings_sections( 'cah_ajax_query_menu_adv' );

                submit_button( 'Save Settings' );
                ?>
                <input id="reset-defaults" class="button button-primary" type="button" value="Reset to Defaults">
            </form>
        </div>
    <?php
} // End cah_ajax_query_menu_adv_html


/**
 * The AJAX function to delete a query from the main options page. I didn't include a _nopriv action,
 * because only registered admin users should be able to mess with this stuff.
 *
 * @return  JSON    $resp   The new options for writing to the existing queries table with JS. Technically
 *                              echoed, rather than returned, but with AJAX it's the same difference.
 */
add_action( 'wp_ajax_cah_ajax_delete_query', 'cah_ajax_delete_query' );
function cah_ajax_delete_query() {

    // Get current options.
    $options = get_option( 'CAQ_options' );

    // The index to be deleted, cast to an integer, in case type presents an issue.
    $del_index = (int) $_POST['delete_index'];

    // Delete the query at the specified index and get rid of the empty element.
    unset( $options[ $del_index ] );
    $options2 = array_values( $options );

    // Encode the array into a JSON string and send it back.
    $resp = json_encode( $options2 );

    echo $resp;

    // Build the array that will serve as the $values array in the validation function,
    // so the correct element can be deleted and updated in the wp_options table. We
    // only set the fields here that we're going to need.
    $options_update = array(
        'do_what' => 'delete',
        'index' => $del_index
    );

    // Call update_option(), which will trigger our custom validation function.
    update_option( 'CAQ_options', $options_update );

    // Kill the server-side process, so we don't get a White Screen of Death.
    wp_die();
}


/**
 * The AJAX function to edit a query from the main options page. I didn't include a _nopriv
 * action, because only registered admin users should be able to use this function.
 *
 * @return  JSON    $resp   The new options for writing to the existing queries table with JS. Technically
 *                              echoed, rather than returned, but with AJAX it's the same difference.
 */
add_action( 'wp_ajax_cah_ajax_edit_query', 'cah_ajax_edit_query' );
function cah_ajax_edit_query() {

    // Get the current options.
    $options = get_option( 'CAQ_options' );

    // Because this information is being sent via AJAX request, the $_POST['values'] field contains
    // the array object that would normally be in the $_REQUEST. We parse it from JSON, and have to
    // strip the slashes first because the double-quotes come through escaped. We also set the second
    // parameter to true so that it will return an array, rather than an object.
    $values = json_decode( stripslashes( $_POST['values'] ), true );

    // The index of the option to update, cast to an integer in case it matters down the road.
    $edit_index = (int) $values['index'];

    // Empty array to hold the new values.
    $edit_option = array();

    // Build the edited array.
    $edit_option['query_name'] = $values['query_name'];
    $edit_option['page_slug'] = $values['page_slug'];
    $edit_option['display_as'] = $values['display_as'];
    $edit_option['post_type'] = $values['post_type'];
    $edit_option['persistent_category'] = $values['persistent_category'];
    $edit_option['paginated'] = $values['paginated'];
    $edit_option['posts_per_page'] = $values['posts_per_page'];
    $edit_option['categories'] = $values['categories'];

    // Overwrite the option at the specified index.
    $options[$edit_index] = $edit_option;

    // Encode and echo the response.
    $resp = json_encode( $options );

    echo $resp;

    // Add the "index" and "do_what" fields to $edit_index, so the validation function knows
    // what to do with it.
    $edit_option['index'] = $edit_index;
    $edit_option['do_what'] = 'edit';

    // Call update_option, which triggers our custom validation function.
    update_option( 'CAQ_options', $edit_option );

    // Kill the server process, because nobody likes WSoDs.
    wp_die();
}


/**
 * The AJAX function for resetting the default values in Advanced Options.
 *
 * @return  boolean         Returns "true" as a garbage value, just so the JS on the
 *                              front end knows that it worked and it's clear to delete
 *                              the values in the input elements.
 */
add_action( 'wp_ajax_cah_ajax_reset_defaults', 'cah_ajax_reset_defaults' );
function cah_ajax_reset_defaults() {

    // Get the current advanced options.
    $adv_options = get_option( 'CAQ_options_advanced' );

    // Iterate through the array and zero out all the values.
    foreach( $adv_options as $key => $value ) {

        $adv_options[$key] = '';
    }

    // Update the option with the new, empty array.
    update_option( 'CAQ_options_advanced', $adv_options );

    // Tell the JS that we succeeded.
    echo "true";

    // Kill the server process and avoid WSoD.
    wp_die();
}
?>
