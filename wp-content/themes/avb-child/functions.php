<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit; 

// Load custom styles and scripts for the AVB Chat Widget
function avb_enqueue_chat_assets() {
    $theme_uri = get_stylesheet_directory_uri();
    
    // Load CSS (Styles for the floating widget and chat window)
    wp_enqueue_style( 'avb-chat-style', $theme_uri . '/assets/avb-chat.css', array('parent-style'), '1.0' );

    // Load JavaScript (The heart of the conversational logic)
    wp_enqueue_script( 'avb-chat-logic', $theme_uri . '/assets/avb-chat.js', array( 'jquery' ), '1.0', true ); 
    // The 'true' loads the script in the footer for optimal performance (WPO)
    
    // Pass essential WP REST API data to the JavaScript (Needed for Nonce and API URL)
    wp_localize_script( 'avb-chat-logic', 'avb_rest_params', array(
        'rest_url' => get_rest_url( null, 'avb/v1/submit-ancestor-data' ),
        'nonce'    => wp_create_nonce( 'wp_rest' ), 
    ) );
}
add_action( 'wp_enqueue_scripts', 'avb_enqueue_chat_assets' );

// Load Parent Theme Styles (Critical for Child Theme function)
function avb_enqueue_parent_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'avb_enqueue_parent_styles' );