<?php
/*
Plugin Name: AVB Core Functionality
Description: Handles all Full-Stack logic for the Ancestral Viability Bot (AVB).
Version: 1.0
Author: Javiera Allende
Author URI: [Your LinkedIn URL]
*/

// Exit if accessed directly (Security measure)
if ( ! defined( 'ABSPATH' ) ) exit;

// --- 1. FUNCTION TO CREATE DB TABLE ON PLUGIN ACTIVATION ---

function avb_create_db_table() {
    global $wpdb;
    $avb_table_name = $wpdb->prefix . 'avb_ancestor_data';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL query to create the table structure for conversational data entry
    $sql = "CREATE TABLE $avb_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        session_id varchar(100) NOT NULL,
        generation tinyint(4) NOT NULL,
        relation_type varchar(50) NOT NULL,
        name varchar(150) NOT NULL,
        birth_location varchar(255) DEFAULT '' NOT NULL,
        birth_date varchar(50) DEFAULT '' NOT NULL,
        client_email varchar(100) NOT NULL,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql ); // Safely creates or updates the table structure
}
// Run the table creation function ONCE on plugin activation
register_activation_hook( __FILE__, 'avb_create_db_table' );

// End of DB table creation and activation hook

// --- 2. CREATE CUSTOM REST ENDPOINT TO RECEIVE DATA ---

function avb_register_rest_endpoint() {
    register_rest_route( 'avb/v1', '/submit-ancestor-data', array(
        'methods' => 'POST', // Only allow POST requests (data submission)
        'callback' => 'avb_handle_ancestor_data',
        'permission_callback' => '__return_true', // Simplification: Allows public access for demonstration
    ) );
}
add_action( 'rest_api_init', 'avb_register_rest_endpoint' );


// 3. API CALLBACK FUNCTION (Logic to insert data)
function avb_handle_ancestor_data( $request ) {
    // SECURITY NOTE: In a real environment, NONCES (Fase 2.4) must be checked here.
    
    // Get parameters from the JSON request body
    $params = $request->get_params();

    // Check for necessary data (example: client email and first ancestor name)
    if ( empty( $params['client_email'] ) || empty( $params['name'] ) ) {
        return new WP_Error( 'missing_data', 'Required fields are missing.', array( 'status' => 400 ) );
    }

    // Insert data into the MySQL table (avb_ancestor_data)
    global $wpdb;
    $table_name = $wpdb->prefix . 'avb_ancestor_data';

    $result = $wpdb->insert(
        $table_name,
        array(
            'session_id' => sanitize_text_field( $params['session_id'] ), // Unique ID for the conversation
            'generation' => intval( $params['generation'] ),
            'relation_type' => sanitize_text_field( $params['relation_type'] ),
            'name' => sanitize_text_field( $params['name'] ),
            'birth_location' => sanitize_text_field( $params['birth_location'] ),
            'birth_date' => sanitize_text_field( $params['birth_date'] ),
            'client_email' => sanitize_email( $params['client_email'] ),
        )
    );

    if ( $result ) {
        // Return a successful response to the JavaScript Front End
        return new WP_REST_Response( array( 'message' => 'Ancestor data saved successfully!', 'id' => $wpdb->insert_id ), 200 );
    } else {
        // Return a database error response
        return new WP_Error( 'db_error', 'Could not save data to database.', array( 'status' => 500 ) );
    }
}
// End of Custom REST Endpoint

// --- 4. WEBHOOK FUNCTION (SIMULATE CRM TRANSFER) ---

function avb_send_webhook_to_crm( $ancestor_data ) {
    // SECURITY NOTE: This simulates sending the qualified lead to a CRM WebHook URL.
    $crm_webhook_url = 'https://api.simulated-crm.com/lead-intake-endpoint'; 

    // Structure the data as the CRM would expect (JSON payload)
    $payload = array(
        'client_name'   => $ancestor_data['name'],
        'client_email'  => $ancestor_data['client_email'],
        'viability_data' => array(
            'generation_level' => $ancestor_data['generation'],
            'birth_location'   => $ancestor_data['birth_location'],
            'document_status'  => 'Unverified' // Placeholder status from initial data capture
        ),
        'internal_token' => 'AVB-SECURE-TOKEN-123' // Simulating a required API token (Fase 2.4 Security)
    );

    // WordPress function to send a POST request
    $response = wp_remote_post( $crm_webhook_url, array(
        'method' => 'POST',
        'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
        'body'    => wp_json_encode( $payload ),
        'data_format' => 'body',
        'timeout' => 10, // Timeout set to 10 seconds (standard practice)
    ));

    // For the demonstration, we just return the payload structure for logging
    if ( is_wp_error( $response ) ) {
        // Handle error status for logging (Fase 2.5)
        return array('status' => 'error', 'message' => $response->get_error_message());
    } else {
        // Simulate a successful response from the CRM
        return array('status' => 'success', 'payload_sent' => $payload);
    }
}
// End of WebHook function