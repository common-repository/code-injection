<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

use WP_REST_Request;

/**
 * This class handles the rendering of code injection blocks.
 * 
 * @since 2.4.14
 */
final class Block{

    private static $instance = null;

    private $database = null;


    /**
     * @since 2.5.0
     */
    private function __construct($database)
    {
        $this->database = $database;

        add_action('init', array($this, '_register_block_type')); 
        add_action('rest_api_init', array($this, '_rest_api_init'));
        add_filter('ci_localize_obj', array($this, '_ci_localize_obj'));
    }



     /**
     * Initialize the Block class.
     *
     * @since 2.4.14
     */
    static function init($database)
    {

        if(!is_null(self::$instance)){
            return null;
        }

        self::$instance = new self($database);
    }



    /**
     * Register a custom REST API route for rendering code.
     *
     * @since 2.4.14
     * @access private
     */
    public function _rest_api_init()
    {
        register_rest_route('ci/v1', '/render-code', [
            'methods' => 'POST',
            'callback' => function(WP_REST_Request $request) {
                // Get the code ID from the request data
                $codeId = $request->get_param('codeId');
                
                $renderedHtml = $this->_render_code_injection_block(array("codeId" => $codeId  ));
                
                // Return the rendered HTML as the response
                return [
                    'html' => $renderedHtml,
                ];
            },
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }



    /**
     * Register a custom block type for the block editor.
     *
     * @since 2.4.14
     * @access private
     */
    public function _register_block_type()
    {
        if (!function_exists('register_block_type')) {
            // Block editor is not available.
            return;
        }

        register_block_type('ci/inject', array(
            'render_callback' => array($this , '_render_code_injection_block')
        ));
    }


    /**
     * Render a code injection block.
     *
     * @since 2.4.14
     * @access private
     */
    public function _render_code_injection_block($attributes)
    {
        $codeId = $attributes['codeId'];
        if ($codeId !== '0') {
            return do_shortcode("[inject id='$codeId']");
        }
    }


    /**
     * Localize an object with data related to code injections.
     *
     * @since 2.4.14
     * @access private
     */
    public function _ci_localize_obj($object) {

        if(!is_admin()){
            return $object;
        }

        $codes =  $this->database->get_codes() ;

        $codes = array_map(function($item){

            $code_slug = Metabox::get_code_slug($item);

            $options = maybe_unserialize($item->meta_value);

            extract($options);

            // Check if the code is intended to be a plugin
            $is_plugin = isset($code_is_plugin) && $code_is_plugin == '1';

            // If code_enabled is not set, default to false
            if (!isset($code_enabled)) {
                $code_enabled = false;
            }

            // Check the code's status
            if (!CodeType::check_code_status($item)) {
                return null; // Skip codes with invalid status
            }

            // Skip plugins or disabled plugins
            if ($is_plugin || $code_enabled == false) {
                return null;
            }

            return array(
                'id'    => $item->ID,
                'title' => empty($code_slug) ? $item->post_title : $code_slug,
                'value' => $item->post_title
            );

        }, $codes );

        $object['codes'] = array_filter( $codes );

        return $object;

    }


}