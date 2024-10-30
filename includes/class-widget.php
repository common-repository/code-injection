<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

use WP_Widget;

/**
 * Custom WordPress widget for Code Injection.
 *
 * This class extends the WP_Widget class to create a custom widget named 'Code Injection'.
 * It allows users to insert code snippets in HTML, CSS, and JavaScript into their website's sidebar
 * or other widgetized areas. The widget provides a user-friendly interface to select from available code snippets.
 *
 * @subpackage Widget
 *
 * @since 0.9.0
 */
final class Widget extends WP_Widget
{

    private static $instance = null;

    private static $database = null;


    /**
     * Constructor for the Widget class.
     *
     * @since 0.9.0
     */
    private function __construct()
    {

        parent::__construct(
            'wp_code_injection_plugin_widget',
            esc_html__('Code Injection', 'code-injection'),
            array(
                'description' => esc_html__("This plugin allows you to effortlessly create custom ads for your website. Inject code snippets in HTML, CSS, and JavaScript, write and run custom plugins on-the-fly, and take your website's capabilities to the next level.", 'code-injection')
            )
        );

    }



    /**
     * @since 2.5.0
     */
    public static function create( $database ){

        if(!is_null(self::$instance)){
            return  null;
        }

        self::$database = $database;

        self::$instance = new self();

        return self::$instance;

    }



    /**
     * Outputs the content of the widget.
     *
     * Displays the selected code snippet in the widget area based on the user's choice.
     *
     * @param array $args Widget arguments.
     * @param array $instance Saved values from the widget form.
     *
     * @since 0.9.0
     */
    function widget($args, $instance)
    {
        $title = apply_filters('widget_title', isset( $instance['title'] ) ? $instance['title'] : '0');

        if ($title !== '0') {
            echo do_shortcode("[inject id='$title']");
        }
    }


    /**
     * Outputs the widget form in the WordPress admin.
     *
     * Provides a form to select and display available code snippets as options in the widget settings.
     *
     * @param array $instance Previously saved values from the widget form.
     *
     * @since 0.9.0
     */
    function form($instance)
    {

        $title = isset($instance['title']) ? $instance['title'] : 'code-#########';
        $codes = self::$database->get_codes();

        // Filter and retain only published code snippets
        $published_codes = array_filter($codes, function ($item) {
            return $item->post_status === 'publish';
        });

        // Generate dropdown options for available code snippets
        $options = '<option value="0">' . esc_html__("— Select —", "code-injection") . '</option>';
        foreach ($published_codes as $code) {
            $codeTitle = get_post_meta($code->ID, "code_slug", true) ?: $code->post_title;
            $selected = selected($code->post_title, $title, false);
            $options .= sprintf('<option %1$s value="%2$s">%3$s</option>', $selected, esc_attr($code->post_title), $codeTitle);
        }

        $fieldId = $this->get_field_id('title');
        $fieldName = $this->get_field_name('title');
        $label = esc_html__('Code ID:', 'code-injection');

        printf('<p><label for="%1$s">%2$s</label><select style="width:100%;" id="%1$s" name="%2$s">%3$s</select></p>', $fieldId, $label, $fieldId, $fieldName, $options);
    }



    /**
     * Updates the widget settings when saved.
     *
     * Sanitizes and stores the selected code snippet ID.
     *
     * @param array $new_instance New settings for the widget.
     * @param array $old_instance Old settings for the widget.
     *
     * @return array Updated settings for the widget.
     *
     * @since 0.9.0
     */
    function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
    
}
