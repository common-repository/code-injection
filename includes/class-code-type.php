<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

use DateInterval;
use DateTime;

final class CodeType
{

    private static $instance = null;

    private static $not_ready_states = array('private', 'draft', 'trash', 'pending');

    private $database = null;


    /**
     * @since 2.5.0
     */
    private function __construct( $database )
    {

        $this->database = $database;

        add_action('init', array( $this , '_create_posttype'));
        add_action("admin_head", array( $this , '_admin_head'));
        add_action("admin_enqueue_scripts", array( $this , '_enqueue_scripts'), 51);
        add_action("manage_code_posts_custom_column", array( $this , '_manage_code_posts_custom_column'), 10, 2);
        add_action("restrict_manage_posts",  array( $this , 'ـfilter_codes_by_taxonomies'), 10, 2);
        add_action("wp_ajax_code_stats", array( $this , '_get_code_stats'));
        add_action("wp_ajax_code_generate_title", array( $this , '_ajax_generate_post_title'));

        add_filter("title_save_pre", array( $this , '_auto_generate_post_title'), 10, 1);
        add_filter("user_can_richedit", array( $this , '_disable_wysiwyg'));
        add_filter("post_row_actions", array( $this , '_custom_row_actions'), 10, 2);
        add_filter("manage_code_posts_columns", array( $this , '_manage_code_posts_columns'));
    }



    /**
     * @since 2.4.2
     */
    static function init( $database )
    {

        if(!is_null(self::$instance)){
            return null;
        }

        self::$instance = new self($database);

    }



    /**
     * @since 2.2.8
     * @access private
     */
    public function _enqueue_scripts()
    {
        if (!Helpers::is_code_page()) {
            return;
        }
        
        AssetManager::enqueue_editor_scripts();
    }



    /**
     * @since 2.4.12
     */
    static function check_code_status($code){

        $status = get_post_status( $code );

        if($status == "private" && !is_user_logged_in())
        {
            return false;
        }

        if($status != "private" && $status != "publish")
        {
            return false;
        }


        return true;
    }


    /**
     * @since 2.4.5
     * @access private
     */
    public function _get_code_stats()
    {

        global $wpdb;

        check_ajax_referer("code-injection-ajax-nonce");


        if (!isset($_GET["id"])) {
            exit;
        }

        $post_id = $_GET["id"];
        $expires = 60 * 5;


        header("Pragma: public", true);
        header("Cache-Control: maxage=$expires public, no-transform", true);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT', true);


        // get GMT
        $cdate = current_time('mysql', 1);

        // heatmap ========================
        $start = new DateTime($cdate);
        $start->sub(new DateInterval('P6D'));  // past 6 days

        $end = new DateTime($cdate); // today

        $hmQuery = $this->database->get_weekly_report_query($post_id, $start, $end);

        $hmData = $wpdb->get_results($hmQuery, ARRAY_A);

        $heatmap = new Heatmap($hmData);

        $heatmap->render();

        echo Heatmap::map(); // color map


        // barchart ========================

        $start = new DateTime($cdate);

        $year = intval($start->format("Y"));

        $month = intval($start->format("m"));

        $length = intval(date('t', mktime(0, 0, 0, $month, 1, $year)));

        $bcDataHolder = array_fill(0, $length, array(
            "value" => 0
        ));

        $bcDataHolder = array_map(function ($item) {
            $index = $item + 1;
            return array(
                "value" => 0,
                "index" => $index < 10 ? "0$index" : $index
            );
        }, array_keys($bcDataHolder));

        $month = intval($start->format("m"));

        $start = new DateTime("$year-$month-01"); // this month

        $end = new DateTime("$year-$month-$length");

        $bcQuery = $this->database->get_monthly_report_query($post_id, $start, $end);

        $bcData = $wpdb->get_results($bcQuery, ARRAY_A);

        foreach ($bcData as $d) {
            $bcDataHolder[intval($d['day']) - 1] = array(
                "value" => $d["total_hits"],
                "index" => $d["day"] < 10 ? "0{$d["day"]}" : $d["day"]
            );
        }

        $barchart = new Barchart($bcDataHolder, 299,  50, 2);


        echo "<div class=\"ci-barchart__container\">";

        $barchart->render();

        echo "<span class=\"month\">" . date("M") .
            "</span></div>";

        exit;
    }


    /**
     * @since 2.2.8
     * @access private
     */
    public function ـfilter_codes_by_taxonomies($post_type, $which)
    {

        if ('code' !== $post_type)
            return;

        $taxonomies = array('code_category');

        foreach ($taxonomies as $taxonomy_slug) {

            // Retrieve taxonomy data
            $taxonomy_obj = get_taxonomy($taxonomy_slug);
            $taxonomy_name = $taxonomy_obj->labels->name;

            // Retrieve taxonomy terms
            $terms = get_terms($taxonomy_slug);

            // Display filter HTML
            echo "<select name='{$taxonomy_slug}' id='{$taxonomy_slug}' class='postform'>";
            echo '<option value="">' . sprintf(esc_html__('Show All %s', "code-injection"), $taxonomy_name) . '</option>';
            foreach ($terms as $term) {
                printf(
                    '<option value="%1$s" %2$s>%3$s (%4$s)</option>',
                    $term->slug,
                    ((isset($_GET[$taxonomy_slug]) && ($_GET[$taxonomy_slug] == $term->slug)) ? ' selected="selected"' : ''),
                    $term->name,
                    $term->count
                );
            }
            echo '</select>';
        }
    }


    /**
     * @since 2.2.8
     * @access private
     */
    public function _admin_head()
    {

        if (!Helpers::is_code_page()) {
            return;
        }

        remove_action('media_buttons', 'media_buttons');

        $template = file_get_contents( __CI_PATH__ . '/assets/temp/admin.temp');

        printf($template , Helpers::get_asset_url('assets/js'));

    }



    /**
     * @since 2.2.8
     * @access private
     */
    public function _custom_row_actions($actions, $post)
    {

        if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {

            unset($actions['inline hide-if-no-js']);

            $status = get_post_status($post);

            $needles = array($post->post_title, '”', '“');

            if (isset($actions['edit'])) {
                $actions['edit'] = str_replace($needles, '', $actions['edit']);
            }

            if (isset($actions['trash'])) {
                $actions['trash'] = str_replace($needles, '', $actions['trash']);
            }


            if (!in_array($status, self::$not_ready_states)) {

                $cid_title = esc_html__("Copy the Code ID into the Clipboard", "code-injection");
                $cid_text = esc_html__("Copy CID", "code-injection");

                $actions['copy_cid'] = "<a href=\"javascript:window.ci.ctc('#cid-$post->ID');\" title=\"$cid_title\" rel=\"permalink\">$cid_text</a>";
            }
        }

        return $actions;
    }

    /**
     * @since 2.2.8
     * @access private
     */
    public function _auto_generate_post_title($title)
    {

        global $post;

        if (wp_is_post_autosave($post)) {
            return $title;
        }

        if (wp_is_post_revision($post)) {
            return $title;
        }

        if (isset($post->ID)) {

            if ('code' !== get_post_type($post->ID)) {
                return $title;
            }

            if (empty($_POST['title'])) {

                if (!empty($title) && $title !== "Auto Draft") {
                    return $title;
                }

                $title = self::generate_id('code-');

            } else {

                $title = $_POST['title'];
            }
        }

        return $title;
    }



    /**
     * @since 2.4.9
     * @access private
     */
    public function _ajax_generate_post_title()
    {

        check_ajax_referer("code-injection-ajax-nonce");

        wp_send_json_success(self::generate_id('code-'));

    }



    /**
     * @since 2.4.12
     */
    private static function generate_id($prefix = '')
    {
        return $prefix . md5(uniqid(random_int(0, 1), true));
    }



    /**
     * @since 2.2.8
     * @access private
     */
    public function _disable_wysiwyg($default)
    {

        if (Helpers::is_code_page()) {
            return false;
        }

        return $default;
    }



    /**
     * @since 2.4.12
     * @access private
     */
    public function _create_posttype()
    {

        $code_lables = array( 
            'name'                  => esc_html_x('Codes', 'post type general name', 'code-injection'), 
            'singular_name'         => esc_html_x('Code', 'post type singular name', 'code-injection'), 
            'add_new'               => esc_html_x('Add New Code', 'post type add new item', 'code-injection'), 
            'add_new_item'          => esc_html_x('Add New Code', 'post type add new item', 'code-injection'),
            'edit_item'             => esc_html_x('Edit Code', 'post type edit item', 'code-injection'), 
            'new_item'              => esc_html_x('New Code', 'post type new item', 'code-injection'), 
            'search_items'          => esc_html_x('Search Codes', 'post type search items', 'code-injection'),
            'not_found'             => esc_html_x('No codes found', 'post type not found', 'code-injection'), 
            'not_found_in_trash'    => esc_html_x('No codes found in Trash', 'post type not found in trash', 'code-injection'), 
            'all_items'             => esc_html_x('All Codes', 'post type all items', 'code-injection'), 
            'name_admin_bar'        => esc_html_x('Code', 'post type name on admin bar', 'code-injection'), 
            'archives'              => esc_html_x('Code Archives', 'post type archives', 'code-injection'), 
            'attributes'            => esc_html_x('Code Attributes', 'post type attributes', 'code-injection'), 
            'parent_item_colon'     => esc_html_x('Parent Code:', 'post type parent item', 'code-injection'), 
            'view_item'             => esc_html_x('View Code', 'post type view item', 'code-injection'), 
            'view_items'            => esc_html_x('View Codes', 'post type view items', 'code-injection'), 
            'update_item'           => esc_html_x('Update Code', 'post type update item', 'code-injection'), 
            'insert_into_item'      => esc_html_x('Insert into code', 'post type insert into item', 'code-injection'),
            'uploaded_to_this_item' => esc_html_x('Uploaded to this code', 'post type uploaded to this item', 'code-injection'), 
            'filter_items_list'     => esc_html_x('Filter codes list', 'post type filter items list', 'code-injection'), 
            'items_list_navigation' => esc_html_x('Codes list navigation', 'post type items list navigation', 'code-injection'), 
            'items_list'            => esc_html_x('Codes list', 'post type items list', 'code-injection') 
        );


        register_taxonomy(
            'code_category',
            'code',
            array(
                'show_admin_column' => true,
                'public' => false,
                'show_ui' => true,
                'rewrite' => false,
                'hierarchical' => true
            )
        );


        register_post_type(
            'code',
            array(
                'label'       => esc_html_x('Codes', 'post type label', "code-injection"),
                'description' => esc_html_x('Custom post type for the code injection plugin', 'post type description', "code-injection"),
                'menu_icon'   => 'dashicons-editor-code',
                'labels'      => $code_lables,
                'public'      => false,
                'show_ui'     => true,
                'rewrite'     => false,
                'query_var'   => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'supports' => array('author', 'revisions', 'title', 'editor'),
                'capability_type' => ['code', 'codes'],
                'can_export' => true,
                'map_meta_cap' => true
            )
        );
    }



    /**
     * @since 2.2.8
     * @access private
     */
    public function _manage_code_posts_columns($columns)
    {

        return array(
            'id' => esc_html__("Code", "code-injection"),
            'info' => esc_html__("Info", "code-injection"),
            'statistics' => esc_html__("Hits", "code-injection")
        );

    }



    /**
     * @since 2.2.8
     * @access private
     */
    public function _manage_code_posts_custom_column($column, $post_id)
    {

        switch ($column) {
            case 'info':

                $code = get_post($post_id);

                $code_options = Metabox::get_code_options($code);

                $categories = get_the_terms($code, 'code_category');

            ?>

                <dl>

                    <?php if (is_array($categories) && count($categories) > 0) : ?>
                        <dt>
                            <strong><?php esc_html_e("Categories", "code-injection"); ?></strong>
                        <dt>
                        <dd>
                            <?php
                            foreach ($categories as $c) {
                                echo "<span>$c->name<span>,";
                            }
                            ?>
                        <dd>
                        <?php endif; ?>

                        <dt>
                            <strong><?php esc_html_e("Author", "code-injection"); ?></strong>
                        <dt>
                        <dd>
                            <?php
                            echo esc_html(get_the_author_meta('display_name', $code->post_author) .
                                " — <" . get_the_author_meta('user_email', $code->post_author) . ">");
                            ?>
                        <dd>
                        <dt>
                            <strong><?php esc_html_e("Date", "code-injection"); ?></strong>
                        <dt>
                        <dd>
                            <?php echo date_i18n('F j, Y - g:i a', strtotime($code->post_modified)); ?>
                        <dd>
                </dl>

                <ul class="ci-codes__info">

                    <?php
                    $arrow = "<i class=\"arrow-down\"></i>";

                    // $revisions = wp_get_post_revisions($post_id);

                    if ($code_options['code_is_plugin'] == true) {

                        echo "<li class=\"plugin\"><span>" .  esc_html__("As Plugin", "code-injection") . "$arrow</span></li>";
                    } else {

                        if ($code_options['code_is_publicly_queryable'] == true) {

                            echo "<li class=\"queryable\"><span>" .  esc_html__("Publicly Queryable", "code-injection") . "$arrow</span></li>";

                            if ($code_options['code_no_cache'] == false) {
                                echo "<li class=\"cache\"><span>" .  esc_html__("Caching Enabled", "code-injection") . "$arrow</span></li>";
                            }

                            echo "<li class=\"type\"><span><strong>" .  esc_html__("Type: ", "code-injection") . "</strong>" . $code_options['code_content_type'] . "$arrow</span></li>";
                        }

                        if ($code_options['code_tracking'] == true) {
                            echo "<li class=\"trackable\"><span>" .  esc_html__("Tracking Enabled", "code-injection") . "$arrow</span></li>";
                        }
                    }

                    echo "</ul>";

                    break;
                case 'id':

                    $code = get_post($post_id);

                    $status = get_post_status($post_id);

                    $code_options = Metabox::get_code_options($code);

                    ?>
                    <p class="ci-codes__description">
                        <?php echo esc_html($code_options['code_description']); ?> — <strong><?php esc_html_e(ucwords($status)); ?></strong>
                    </p>

                    <?php
                    if (in_array($status, self::$not_ready_states)) {
                        break;
                    }

                    if ($code_options['code_enabled'] != true) {

                        echo "<p class=\"ci-codes__suspended ci-codes__suspension-bg\">" . esc_html__("Suspended", "code-injection") . "</p>";
                    }

                    ?>

                    <dl>
                        <dt>
                            <strong><?php esc_html_e("Code ID", "code-injection") ?></strong>
                        <dt>
                        <dd>
                            <code id="<?php echo "cid-$code->ID"; ?>" style="font-size:11px;"><?php echo $code->post_title; ?></code>
                        <dd>
                    </dl>

    <?php

                    break;

                case 'statistics':

                    $code = get_post($post_id);

                    $code_options = Metabox::get_code_options($code);


                    if ($code_options['code_tracking'] != true || $code_options['code_is_plugin'] == true) {

                        echo "<div class=\"ci-codes__heatmap-na\">N/A</div>";
                    } else {

                        echo "<div data-post=\"$post_id\" class=\"ci-codes__chart-placeholder\"></div>" .
                            "<div class=\"ci-codes__spinner\"></div>";
                    }


                    break;
            }
        }
    }
