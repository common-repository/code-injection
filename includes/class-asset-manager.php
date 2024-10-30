<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

/**
 * The AssetManager class handles the enqueuing of styles and scripts required for the Code Injection plugin's functionality.
 * It registers and enqueues various styles and scripts for both the admin panel and specific plugin features. The class is
 * responsible for loading code editor styles, Monaco editor scripts, tag editor styles and scripts, essential plugin scripts,
 * and custom styles for the plugin settings page. It also localizes script data to provide necessary variables to JavaScript
 * functions. The class aids in maintaining the presentation and interactivity of the plugin's user interface.
 *
 * @since 2.4.2
 */
final class AssetManager
{


    private static $CI_LOCALIZE_OBJ = '_ci';


    /**
     * Initialize the Asset Manager
     *
     * Registers and enqueues necessary styles and scripts for the Code Injection plugin's functionality.
     *
     * @since 2.4.2
     */
    static function init()
    {
        add_action('admin_enqueue_scripts', array(__CLASS__, '_enqueue_scripts'), 50);
        add_action('enqueue_block_editor_assets', array(__CLASS__, '_enqueue_block_editor_assets'));
    }



    /**
     * Enqueue Scripts and Styles
     *
     * Handles the enqueuing of scripts and styles for the Code Injection plugin's functionality. This includes loading
     * code editor styles, scripts, Monaco editor, tag editor styles and scripts, and plugin-specific styles and scripts
     * based on whether the plugin's settings page is being accessed or not.
     *
     * @access private
     * @since 2.2.8
     */
    static function _enqueue_scripts()
    {

        // Array of localized texts for internationalization
        $texts = array(
            "The File is too large. Do you want to proceed?",
            "Are you sure? You are about to replace the current code with the selected file content.",
            "The selected file type is not supported.",
            "Copy"
        );

        // Internationalization for JavaScript
        $i18n = array(
            'code-injection' => array(
                'texts'         => $texts , 
                'translates'    => array_map(function ($item) {
                    return esc_html__($item, "code-injection");
                }, $texts)
            )
        );


        // Register custom styles and scripts
        wp_register_style('ci-custom-code-editor', Helpers::get_asset_url('/css/code-editor.css'), array(), __CI_VERSION__, 'all');
        wp_register_script('ci-monaco-editor-loader', 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.21.2/min/vs/loader.min.js', array('jquery'), null, true);
        wp_register_script('ci-editor', Helpers::get_asset_url('/js/code-editor.js'), array(), __CI_VERSION__, false);


        // Enqueue essential scripts
        wp_enqueue_script('ci-essentials', Helpers::get_asset_url('/js/essentials.js'), array('jquery'), __CI_VERSION__, true);


        // Localize essential scripts with relevant data
        wp_localize_script('ci-essentials', self::$CI_LOCALIZE_OBJ , apply_filters('ci_localize_obj' , array(
            'ajax_url'      => admin_url('admin-ajax.php'),
            "ajax_nonce"    => wp_create_nonce("code-injection-ajax-nonce"),
            "is_rtl"        => is_rtl() ? "true" : "false",
            "i18n"          => $i18n
        )));


        // Enqueue plugin-specific styles
        wp_enqueue_style('ci-styles', Helpers::get_asset_url('/css/wp-code-injection-admin.css'), array(), __CI_VERSION__, 'all');


        // Enqueue additional styles and scripts for settings page
        if (Helpers::is_settings_page()) {
            wp_enqueue_style('ci-tag-editor', Helpers::get_asset_url('/css/jquery.tag-editor.css'), array(), __CI_VERSION__, 'all');
            wp_enqueue_script('ci-caret', Helpers::get_asset_url('/js/jquery.caret.min.js'), array('jquery'), __CI_VERSION__, false);
            wp_enqueue_script('ci-tag-editor', Helpers::get_asset_url('/js/jquery.tag-editor.min.js'), array('jquery', 'ci-caret'), __CI_VERSION__, false);
            wp_enqueue_script('ci-code-injection', Helpers::get_asset_url('/js/wp-ci-general-settings.js'), array('jquery'), __CI_VERSION__, true);
        }
    }



    /**
     * @access private
     * @since 2.4.14
     */
    static function _enqueue_block_editor_assets() {

        $deps   = array('wp-blocks', 'wp-element', 'wp-data', 'wp-components');
        $src    = Helpers::get_asset_url('/js/block.js');

        wp_enqueue_script('ci-inject-block' , $src ,  $deps , __CI_VERSION__ , true);

    }





    /**
     * Enqueue Editor Scripts and Styles
     *
     * Enqueues styles and scripts specifically for the code editor component.
     *
     * @since 2.4.2
     */
    static function enqueue_editor_scripts()
    {
        wp_enqueue_style('ci-custom-code-editor');

        wp_enqueue_script('ci-monaco-editor-loader');
        wp_enqueue_script('ci-editor');
    }




}
