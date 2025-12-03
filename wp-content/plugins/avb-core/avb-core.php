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

// ===============================================
// PHASE 1: DATABASE AND SECURITY SETUP
// ===============================================

// 1. FUNCTION TO CREATE DB TABLE ON PLUGIN ACTIVATION
function avb_create_db_table() {
    global $wpdb;
    $avb_table_name = $wpdb->prefix . 'avb_ancestor_data';
    $charset_collate = $wpdb->get_charset_collate();

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
    dbDelta( $sql ); 
}
// Run the table creation function ONCE on plugin activation
register_activation_hook( __FILE__, 'avb_create_db_table' );


// 2. SECURITY CHECK: NONCE VALIDATION FUNCTION (Fase 2.4)
function avb_validate_rest_nonce( $request ) {
    // The nonce token is usually sent in the header by JavaScript
    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error( 'rest_forbidden', __( 'Invalid nonce token. Access denied for security.', 'avb-core' ), array( 'status' => 401 ) );
    }
    return true;
}

// ===============================================
// PHASE 2: API ENDPOINTS AND INTEGRATION
// ===============================================

// 3. CREATE CUSTOM REST ENDPOINT TO RECEIVE DATA (Paso 2.2)
function avb_register_rest_endpoint() {
    register_rest_route( 'avb/v1', '/submit-ancestor-data', array(
        'methods' => 'POST', 
        'callback' => 'avb_handle_ancestor_data',
        'permission_callback' => 'avb_validate_rest_nonce', // NONCE is now enforced here!
    ) );
}
add_action( 'rest_api_init', 'avb_register_rest_endpoint' );


// 4. API CALLBACK FUNCTION (Logic to insert data)
function avb_handle_ancestor_data( $request ) {
    // Get parameters from the JSON request body
    $params = $request->get_params();

    if ( empty( $params['client_email'] ) || empty( $params['name'] ) ) {
        return new WP_Error( 'missing_data', 'Required fields are missing.', array( 'status' => 400 ) );
    }

    // Insert data into the MySQL table (avb_ancestor_data)
    global $wpdb;
    $table_name = $wpdb->prefix . 'avb_ancestor_data';

    $result = $wpdb->insert(
        $table_name,
        array(
            'session_id' => sanitize_text_field( $params['session_id'] ),
            'generation' => intval( $params['generation'] ),
            'relation_type' => sanitize_text_field( $params['relation_type'] ),
            'name' => sanitize_text_field( $params['name'] ),
            'birth_location' => sanitize_text_field( $params['birth_location'] ),
            'birth_date' => sanitize_text_field( $params['birth_date'] ),
            'client_email' => sanitize_email( $params['client_email'] ),
        )
    );

    if ( $result ) {
        // Now, trigger the WebHook to the external CRM (Paso 2.3)
        $webhook_status = avb_send_webhook_to_crm( $params ); 
        
        return new WP_REST_Response( array( 
            'message' => 'Ancestor data saved successfully!', 
            'id' => $wpdb->insert_id,
            'webhook_status' => $webhook_status['status']
        ), 200 );
    } else {
        return new WP_Error( 'db_error', 'Could not save data to database.', array( 'status' => 500 ) );
    }
}


// 5. WEBHOOK FUNCTION (SIMULATE CRM TRANSFER - Paso 2.3)
function avb_send_webhook_to_crm( $ancestor_data ) {
    // This simulates sending the qualified lead to a CRM WebHook URL.
    $crm_webhook_url = 'https://api.simulated-crm.com/lead-intake-endpoint'; 

    $payload = array(
        'client_name'   => $ancestor_data['name'],
        'client_email'  => $ancestor_data['client_email'],
        'internal_token' => 'AVB-SECURE-TOKEN-123' 
    );

    $response = wp_remote_post( $crm_webhook_url, array(
        'method' => 'POST',
        'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
        'body'    => wp_json_encode( $payload ),
        'timeout' => 10,
    ));

    if ( is_wp_error( $response ) ) {
        // Will be logged by the system in Fase 2.5
        return array('status' => 'webhook_error'); 
    } else {
        // Check for specific CRM response status (e.g., 200 or 201)
        return array('status' => 'success');
    }
}
// End of PHP Logic