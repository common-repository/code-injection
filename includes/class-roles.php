<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

/**
 * Manages custom roles and capabilities related to the Code Injection plugin.
 *
 * This class handles the registration and updates of custom user roles and capabilities for the Code Injection plugin.
 * It ensures that the 'Developer' role is properly registered with specific capabilities required for the plugin's functionality.
 *
 * @since 2.4.12
 */
final class Roles
{
    /**
     * Initializes custom roles and capabilities.
     *
     * Registers actions to perform role registration and capability updates.
     *
     * @since 2.4.12
     */
    public static function init()
    {
        add_action('admin_init', array(__CLASS__, '_register_developer_role'));
        add_action('admin_init', array(__CLASS__, '_update_capabilities'));
    }

    /**
     * Registers the 'Developer' custom role and sets its capabilities.
     *
     * This method registers the 'Developer' role with specific capabilities required for the Code Injection plugin.
     *
     * @access private
     * 
     * @since 2.4.12
     */
    static function _register_developer_role()
    {
        
        // Get the 'Developer' role object
        $developerRole = get_role('developer');

        // Get the stored role version from options
        $roleVersion = get_option('ci_role_version', '');

        // Check if role version matches and 'Developer' role exists
        if ($roleVersion === __CI_VERSION__ && isset($developerRole)) {
            // Role is already registered with correct version, no need to re-register
            return;
        }

        // Remove the existing 'Developer' role if it exists
        remove_role('developer');

        // Add the 'Developer' role with specific capabilities
        add_role(
            'developer',
            esc_html__('Developer', "code-injection"),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            )
        );

        // Update the stored role version to the current version
        update_option('ci_role_version', __CI_VERSION__);

    }

    /**
     * Updates capabilities for specified roles.
     *
     * Adds capabilities related to managing code snippets for 'developer' and 'administrator' roles.
     *
     * @access private
     * 
     * @since 2.2.6
     */
    static function _update_capabilities()
    {
        // List of roles to update capabilities for
        $rolesToUpdate = array('developer', 'administrator');

        // Iterate through each role to update capabilities
        foreach ($rolesToUpdate as $role) {
            // Get the role object for the current role
            $roleObject = get_role($role);

            // Check if the role object exists
            if (!isset($roleObject)) {
                // If role object doesn't exist, move to the next role
                continue;
            }

            // List of capabilities to be added
            $capabilities = array(
                'publish', 'delete', 'delete_others', 'delete_private',
                'delete_published', 'edit', 'edit_others', 'edit_private',
                'edit_published', 'read_private'
            );

            // Iterate through each capability to add to the role
            foreach ($capabilities as $capability) {
                // Add the capability for singular code management
                $roleObject->add_cap("{$capability}_code");

                // Add the capability for plural code management
                $roleObject->add_cap("{$capability}_codes");
            }
        }
    }
}
