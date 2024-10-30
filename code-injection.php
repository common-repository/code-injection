<?php

/**
 * Plugin Name: Code Injection
 * Plugin URI: https://github.com/Rmanaf/wp-code-injection
 * Description: This plugin allows you to effortlessly create custom ads for your website. Inject code snippets in HTML, CSS, and JavaScript, write and run custom plugins on-the-fly, and take your website's capabilities to the next level.
 * Version: 2.5.0
 * Author: Rmanaf
 * Author URI: https://profiles.wordpress.org/rmanaf/
 * License: MIT License
 * License URI: https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE
 * Text Domain: code-injection
 * Domain Path: /languages
 */

use ci\Core;

// Prevent direct access to the plugin file
defined('ABSPATH') or die;

// Define some constants for convenience
define('__CI_FILE__', __FILE__);
define('__CI_URL__', plugin_dir_url(__FILE__));
define('__CI_PATH__', plugin_dir_path(__FILE__));
define('__CI_VERSION__', '2.5.0');

// Require the necessary files for the plugin
require_once __DIR__ . '/includes/class-database.php';
require_once __DIR__ . '/includes/class-heatmap.php';
require_once __DIR__ . '/includes/class-barchart.php';
require_once __DIR__ . '/includes/class-shortcodes.php';
require_once __DIR__ . '/includes/class-roles.php';
require_once __DIR__ . '/includes/class-helpers.php';
require_once __DIR__ . '/includes/class-options.php';
require_once __DIR__ . '/includes/class-metabox.php';
require_once __DIR__ . '/includes/class-helpers.php';
require_once __DIR__ . '/includes/class-asset-manager.php';
require_once __DIR__ . '/includes/class-widget.php';
require_once __DIR__ . '/includes/class-code-type.php';
require_once __DIR__ . '/includes/class-block.php';
require_once __DIR__ . '/includes/class-core.php';

// Setup the plugin
Core::setup();
