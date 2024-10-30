<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */


namespace ci;


/**
 * The Helpers class provides utility functions for various tasks within the code tracking system.
 *
 * @namespace ci
 * @since 2.4.12
 */
final class Helpers
{


    /**
     * Checks if the current page is related to managing code snippets.
     *
     * @return bool True if the current page is a code management page, false otherwise.
     *
     * @since 2.4.12
     */
    static function is_code_page()
    {

        // Check if it's a new code creation page
        if (self::is_edit_page('new')) {
            if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                return true;
            }
        }

        // Check if it's an edit page for an existing code snippet
        if (self::is_edit_page('edit')) {
            global $post;
            if ('code' == get_post_type($post)) {
                return true;
            }
        }

        // If neither edit nor new code page, or post type is not 'code', it's not a code management page
        return false;

    }



    /**
     * Checks if the current page is an edit or new post creation page within the admin area.
     *
     * @param string|null $new_edit Specifies the type of page to check ('edit' for edit page, 'new' for new post creation page).
     * @return bool True if the current page is an edit or new post creation page, false otherwise.
     *
     * @since 2.4.12
     */
    static function is_edit_page($new_edit = null)
    {

        global $pagenow;

        // Check if it's within the admin area
        if (!is_admin()) {
            return false;
        }
    
        switch ($new_edit) {
            // Check if it's an edit page
            case "edit": 
                return in_array($pagenow, array('post.php'));

            // Check if it's a new post creation page
            case "new":
                return in_array($pagenow, array('post-new.php'));

            // Check if it's either an edit or new post creation page
            default:
                return in_array($pagenow, array('post.php', 'post-new.php'));
        }

    }


    /**
     * Checks if the current page is a settings page within the WordPress admin area.
     *
     * @return bool True if the current page is a settings page, false otherwise.
     *
     * @since 2.4.12
     */
    static function is_settings_page()
    {
        // Define the target settings screen ID
        $settings_screen_id = 'ci-general';

        // Check if the required function exists
        if (!function_exists('get_current_screen')) {
            return false;
        }

        // Retrieve the current screen object
        $screen = get_current_screen();
        
        // Check if the screen ID matches the target settings screen ID
        return strpos($screen->id , $settings_screen_id) !== false;
    }




    /**
     * Retrieves the user's IP address from various possible sources.
     *
     * @return string The user's IP address if found, otherwise "Unknown".
     *
     * @since 2.4.12
     */
    static function get_ip_address()
    {
        // List of possible headers and server variables containing the IP address
        $ip_sources = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        // Iterate through each source to find the user's IP address
        foreach ($ip_sources as $source) {
            if (array_key_exists($source, $_SERVER)) {
                foreach (explode(',', $_SERVER[$source]) as $ip) {
                    $ip = trim($ip);

                    // Validate the IP address and exclude private and reserved ranges
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip ?: "Unknown"; // Return the IP address or "Unknown"
                    }
                }
            }
        }

        return "Unknown"; // Return "Unknown" if no valid IP address is found
    }



    /**
     * Retrieves the URL of an asset using the provided relative path.
     *
     * @param string $relative_path The relative path of the asset within the plugin's assets directory.
     * @return string The complete URL of the asset.
     *
     * @since 2.4.12
     */
    static function get_asset_url($relative_path)
    {
        // Construct the URL by appending the relative path to the assets directory path
        $assets_dir_path = trailingslashit("/assets/");
        $asset_url = plugins_url($assets_dir_path . ltrim($relative_path, "/"), __CI_FILE__);

        return $asset_url;
    }
    

}
