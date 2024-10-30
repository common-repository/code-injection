<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;


/**
 * Core Class for Code Injection Plugin
 *
 * The Core class serves as the central orchestrator for the Code Injection plugin. It provides the backbone structure
 * for the plugin's functionalities by organizing and managing its various components and actions.
 */
final class Core
{

    private static $instance = null;

    private $database = null;


    /**
     * @since 2.5.0
     */
    private function __construct()
    {

        // Initialize the Database class
        $this->database = Database::init();


        // Initialize code type management
        CodeType::init( $this->database );

        Block::init( $this->database );

        // Initialize user roles and capabilities
        Roles::init();

        // Initialize metabox handling for posts
        Metabox::init();

        // Initialize asset management for styles and scripts
        AssetManager::init();

        // Initialize custom shortcodes for the plugin
        Shortcodes::init( $this->database );

        // Initialize options management
        Options::init();



        // Hook to load plugins at the plugins_loaded action
        add_action('plugins_loaded', array($this, '_load_plugins'));

        // Hook to check and handle raw content requests
        add_action("template_redirect", array($this, '_check_raw_content'));

        // Hook to register and initialize custom widgets
        add_action('widgets_init', array($this, '_widgets_init'));

        // Hook to load the plugin's text domain for translations
        add_action('plugins_loaded', array($this, '_load_plugin_textdomain'));



        // Check if "Unsafe" settings for shortcodes in widgets are enabled
        if (get_option('ci_unsafe_widgets_shortcodes', 0)) {
            // Apply filters to allow shortcodes in widgets
            add_filter('widget_text', 'shortcode_unautop');
            add_filter('widget_text', 'do_shortcode');
        }
    }



    /**
     * Setup Plugin Components and Actions
     *
     * @since 2.4.12
     */
    static function setup()
    {

        if(!is_null(self::$instance)){
            return null;
        }


        self::$instance = new self();

        

        // Register activation and deactivation hooks for the plugin
        register_activation_hook(__CI_FILE__, array(__CLASS__, '_plugin_activate'));
        register_deactivation_hook(__CI_FILE__, array(__CLASS__, '_plugin_deactivate'));
    }


    /**
     * Load Plugin Text Domain for Localization
     *
     * This method is responsible for loading the plugin's text domain for localization purposes.
     * It allows translations to be loaded from the "languages" directory within the plugin.
     *
     * @access private
     * @since 2.4.12
     */
    public function _load_plugin_textdomain()
    {
        // Load the text domain for translation
        load_plugin_textdomain("code-injection", FALSE, basename(dirname(__CI_FILE__)) . '/languages/');
    }


    /**
     * Load and Execute Unsafe Plugins
     *
     * This private method loads and executes potentially unsafe plugins when the appropriate settings are enabled.
     * It retrieves plugin codes from the database, filters and processes them based on certain conditions, then
     * executes them using `eval()`. The execution is controlled by options and conditions defined in the database.
     *
     * @access private
     * @since 2.2.9
     */
    public function _load_plugins()
    {

        global $wpdb;

        // Check if PHP-based unsafe widgets are allowed
        $use_php = get_option('ci_unsafe_widgets_php', false);

        if (!$use_php) {
            return; // If not allowed, exit early
        }

        // Check if ignoring keys for unsafe widgets
        $ignore_keys = get_option('ci_unsafe_ignore_keys', false);

        // Get keys that trigger activation
        $keys = get_option('ci_unsafe_keys', '');

        // Retrieve all plugin codes from the database
        $codes = $this->database->get_codes();

        // Filter and process plugins based on conditions
        $plugins = array_filter($codes, function ($element) use ($ignore_keys, $keys) {

            $options = maybe_unserialize($element->meta_value);

            extract($options);

            // Check if the code is intended to be a plugin
            $is_plugin = isset($code_is_plugin) && $code_is_plugin == '1';

            // Check if the code should be publicly queryable
            $is_public = isset($code_is_publicly_queryable) && $code_is_publicly_queryable == '1';

            // If code_enabled is not set, default to false
            if (!isset($code_enabled)) {
                $code_enabled = false;
            }

            // Check the code's status
            if (!CodeType::check_code_status($element)) {
                return false; // Skip codes with invalid status
            }

            // Skip codes that are publicly queryable
            if ($is_public) {
                return false;
            }

            // Skip non-plugin codes or disabled plugins
            if (!$is_plugin || $code_enabled == false) {
                return false;
            }

            // If ignoring keys, include all plugins
            if ($ignore_keys) {
                return true;
            }

            // Check if activator key is in the specified keys
            return isset($code_activator_key) && in_array($code_activator_key, $instance->extract_keys($keys));
        });


        // Execute and enable filtered plugins
        foreach ($plugins as $p) {

            // Get code options and disable the plugin initially
            $code_options = Metabox::get_code_options($p->ID);
            $code_options['code_enabled'] = false;

            // Update code options to disable the plugin
            update_post_meta($p->ID, "code_options", $code_options);

            // Execute the plugin code using eval()
            eval("?" . ">" . $p->post_content);

            // Enable the plugin in code options
            $code_options['code_enabled'] = true;
            update_post_meta($p->ID, "code_options", $code_options);
        }

    }


    /**
     * Checks and processes raw content for code injection.
     *
     * This method checks if the request is targeting the home page or front page and if the "raw" parameter is set.
     * It processes and serves raw code content with appropriate headers and caching directives.
     * 
     * @access private
     * @since 2.4.12
     */
    public function _check_raw_content()
    {

        // Check if the request is on the home page or front page
        if (!is_home() && !is_front_page()) {
            return;
        }

        // Check if "raw" parameter is set in the request
        if (!isset($_GET["raw"])) {
            return;
        }

        // Get the code ID from the "raw" parameter
        $codeId = sanitize_text_field($_GET["raw"]);

        // Retrieve code information based on the ID
        $code = $this->database->get_code_by_title($codeId);

        // Check if code exists
        if (!$code) {
            // Record activity for code not found
            $this->database->record_activity(0, null, 2);
            return;
        }


        // Check the code's status
        if (!CodeType::check_code_status($code)) {
            // Skip codes with invalid status
            $this->database->record_activity(0, $codeId, 6, $code->ID);
            return;
        }


        // Extract options from code metadata
        $options = maybe_unserialize($code->meta_value);


        extract($options);

        // Determine code status based on options
        $isActive = isset($code_enabled) && $code_enabled == '1';
        $isPlugin = isset($code_is_plugin) && $code_is_plugin == '1';
        $isPublic = isset($code_is_publicly_queryable) && $code_is_publicly_queryable == '1';
        $noCache = isset($code_no_cache) && $code_no_cache == '1';

        // Check if code should be processed further
        if (!$isActive || $isPlugin || !$isPublic) {
            return;
        }

        // Check if shortcodes should be rendered
        $renderShortcodes = get_option('ci_code_injection_allow_shortcode', false);

        // Record activity for successful code request
        $this->database->record_activity(0, $codeId, 0, $code->ID);

        // Set appropriate content-type header
        header("Content-Type: $code_content_type; charset=UTF-8", true);


        // Set caching headers based on options
        if ($noCache) {
            header("Pragma: no-cache", true);
            header("Cache-Control: no-cache, must-revalidate, max-age=0", true);
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT", true);
        } else {
            $cacheMaxAge = get_option('ci_code_injection_cache_max_age', '84600');
            header("Pragma: public", true);
            header("Cache-Control: max-age=$cacheMaxAge, public, no-transform", true);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheMaxAge) . ' GMT', true);
        }


        // Output processed code content and exit
        if ($renderShortcodes) {
            exit(do_shortcode($code->post_content));
        } else {
            exit($code->post_content);
        }

    }


    /**
     * Initialize and register custom widgets.
     *
     * @access private
     * @since 2.4.12
     */
    public function _widgets_init()
    {
        // Register the custom widget
        register_widget(Widget::create( $this->database ));
    }


    /**
     * Activate Plugin Functionality and Flush Rewrite Rules
     *
     * This private method is called when the plugin is activated. It performs necessary actions to ensure
     * that the plugin's functionality is activated and that rewrite rules are updated to handle new URLs.
     *
     * @access private
     * @since 2.4.12
     */
    static function _plugin_activate()
    {
        // Flush rewrite rules to update URL handling after activation
        flush_rewrite_rules();
    }


    /**
     * Deactivate plugin functionality.
     *
     * This private method is responsible for deactivating the plugin's functionality when the plugin is deactivated.
     * It performs various cleanup actions, including flushing rewrite rules, deleting plugin-related options,
     * and removing a custom role.
     *
     * @access private
     * @since 2.4.12
     */
    static function _plugin_deactivate()
    {
        // Flush rewrite rules to update URL handling after deactivation
        flush_rewrite_rules();

        // Delete plugin-related options from the database
        delete_option('ci_code_injection_db_version');
        delete_option('ci_code_injection_role_version');

        // Remove the custom "developer" role
        remove_role('developer');
    }

}
