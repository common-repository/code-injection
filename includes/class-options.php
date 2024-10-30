<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

/**
 * A class for managing plugin options and settings.
 * 
 * This class handles the registration of settings, options, and the settings page.
 * 
 * @since 2.4.12
 */
final class Options
{

    const option_group = 'ci-general'; // The option group used for registering settings

    /**
     * Initialize the Options class by adding necessary action hooks.
     * 
     * This function is called when the class is loaded and adds action hooks to initialize the admin settings page and menu.
     *
     * @since 2.4.12
     */
    static function init()
    {
        add_action('admin_init', array(__CLASS__, '_admin_init'));
        add_action('admin_menu', array(__CLASS__, '_admin_menu'));
    }

    /**
     * Get the array of unsafe keys from the option.
     *
     * @return array An array of unsafe keys.
     * @since 2.4.12
     */
    static function get_keys()
    {
        return self::extract_keys(get_option('ci_unsafe_keys', ''));
    }

    /**
     * Extract keys from a comma-separated string.
     *
     * @param string $text The comma-separated string containing keys.
     * @return array An array of keys.
     * @since 2.4.12
     */
    private static function extract_keys($text)
    {
        return array_filter(explode(',', $text), function ($elem) {
            return preg_replace('/\s/', '', $elem);
        });
    }

    /**
     * Add the plugin settings page to the admin menu.
     *
     * @since 2.4.12
     * @access private
     */
    static function _admin_menu()
    {
        add_submenu_page(
            'options-general.php',
            __('Code Injection', 'code-injection'),
            __('Code Injection', 'code-injection'),
            'manage_options',
            self::option_group,
            array(__CLASS__, '_settings_page_cb')
        );
    }

    /**
     * Initialize the admin settings and register settings sections and fields.
     *
     * @since 2.4.12
     * @access private
     */
    static function _admin_init()
    {
        // Register a settings section
        add_settings_section('wp_code_injection_plugin', "", "__return_false", self::option_group);

        // Register code-related settings
        register_setting(self::option_group, 'ci_code_injection_cache_max_age', ['default' => '84600']);
        register_setting(self::option_group, 'ci_code_injection_allow_shortcode', ['default' => false]);

        // Register unsafe-related settings
        register_setting(self::option_group, 'ci_unsafe_widgets_shortcodes', ['default' => false]);
        register_setting(self::option_group, 'ci_unsafe_keys', ['default' => '']);
        register_setting(self::option_group, 'ci_unsafe_widgets_php', ['default' => false]);
        register_setting(self::option_group, 'ci_unsafe_ignore_keys', ['default' => false]);

        // Add settings fields
        self::add_settings_field('ci_code_injection_cache_max_age', '84600', esc_html__("Code Options", "code-injection"));
        self::add_settings_field('ci_code_injection_allow_shortcode', false, esc_html__("Shortcodes", "code-injection"));
        self::add_settings_field('ci_unsafe_widgets_shortcodes', false);
        self::add_settings_field('ci_unsafe_widgets_php', false);
        self::add_settings_field('ci_unsafe_ignore_keys', false, esc_html__("Activator Keys", "code-injection"));
        self::add_settings_field('ci_unsafe_keys');
    }



    /**
     * Render the settings page content.
     *
     * This function is called to render the plugin settings page in the WordPress admin.
     * It displays the registered settings sections and fields.
     *
     * @since 2.4.12
     * @access private
     */
    static function _settings_page_cb()
    {

        $title  = esc_html__('Code Injection', "code-injection");

        ob_start();

        settings_fields(self::option_group);

        do_settings_sections(self::option_group);

        submit_button();

        $content = ob_get_clean();

        $template = '<div class="wrap"><h1 class="title">%1$s</h1><form method="POST" action="options.php">%2$s</form></div>';

        printf( $template, $title , $content );

    }



    /**
     * Add a settings field to the options page.
     *
     * This function adds a settings field to the plugin options page in the WordPress admin.
     * It allows users to input values or make selections for various plugin settings.
     *
     * @param string $id The unique identifier for the setting.
     * @param mixed $default The default value for the setting.
     * @param string $title The title of the settings field.
     * @param string $section The settings section to which the field belongs.
     * @param string $page The option group identifier.
     * @since 2.4.12
     * @access private
     */
    private static function add_settings_field($id, $default = '', $title = '', $section = 'wp_code_injection_plugin', $page = self::option_group)
    {
        add_settings_field($id, $title, array(__CLASS__, '_settings_field_cb'),  $page, $section, array('label_for' => $id,  'default'   => $default));
    }


    /**
     * Generate a checkbox input for settings.
     *
     * This function generates a checkbox input field for the plugin settings.
     * It allows users to enable or disable certain plugin features.
     *
     * @param string $key The option key.
     * @param bool $value The current value of the checkbox.
     * @param string $description The description of the checkbox.
     * @since 2.4.12
     */
    private static function checkbox($key, $value, $description)
    {
        printf('<label><input type="checkbox" value="1" id="%1$s" name="%1$s" %2$s />%3$s</label>', $key, checked($value, true, false), $description);
    }


    /**
     * Render the settings field callback.
     *
     * This function renders the content of a settings field callback.
     * It generates different types of inputs based on the field type.
     *
     * @param array $args The arguments for the settings field.
     * @since 2.4.12
     * @access private
     */
    static function _settings_field_cb($args)
    {

        $key = $args['label_for'];
        $default = isset($args['default']) ? $args['default'] : '';
        $value = get_option($key, $default);

        switch ($key) {
            case 'ci_code_injection_cache_max_age':
                printf('<p>%1$s</p>', esc_html__('Cache max-age (Seconds)', 'code-injection'));
                printf('<input class="regular-text" type="number" value="%1$s" id="%2$s" name="%2$s" />', $value, $key);
                printf('<p class="description">%1$s</p>', esc_html__('e.g. 84600', 'code-injection'));
                break;
            case 'ci_unsafe_keys':
                printf('<p class="ack-head-wrapper"><span class="ack-header"><strong>%1$s</strong></span><a class="button ack-new" href="javascript:void(0);" id="ci_generate_key">%2$s</a></p>',
                    esc_html__("Keys:", "code-injection"), esc_html__("Generate Key", "code-injection"));
                printf('<p><textarea data-placeholder="%1$s" class="large-text code" id="%2$s" name="%2$s">%3$s</textarea></p>',
                    esc_html__("Enter Keys:", "code-injection"), $key, $value);
                printf('<p class="description">%1$s</p>', esc_html__('e.g. key-2im2a5ex4, key-6dp7mwt05 ...', 'code-injection'));
                break;
            case 'ci_code_injection_allow_shortcode':
                self::checkbox($key, $value, esc_html__("Allow nested shortcodes", "code-injection"));
                break;
            case 'ci_unsafe_ignore_keys':
                self::checkbox($key, $value, esc_html__("Ignore activator keys", "code-injection"));
                break;
            case 'ci_unsafe_widgets_shortcodes':
                self::checkbox($key, $value, esc_html__("Allow shortcodes in the Custom HTML widget", "code-injection"));
                break;
            case 'ci_unsafe_widgets_php':
                self::checkbox($key, $value, sprintf(esc_html__("Enable %s shortcode", "code-injection"), "<code>[unsafe key='']</code>"));
                printf('<p class="description">%1$s</p>',
                    sprintf(
                        esc_html__('See %1$s for more information.', "code-injection"),
                        sprintf(
                            '<a target="_blank" href="%1$s">%2$s</a>',
                            esc_url('https://github.com/Rmanaf/wp-code-injection/blob/master/README.md'),
                            esc_html__("Readme", "code-injection")
                        )
                    )
                );
                break;
        }
    }
}
