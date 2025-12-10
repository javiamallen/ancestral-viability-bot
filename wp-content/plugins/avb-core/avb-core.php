<?php
/*
Plugin Name: AVB Core Functionality
Description: Handles all Full-Stack logic for the Ancestral Viability Bot (AVB), including DB, REST API, Security, and Logging.
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
register_activation_hook( __FILE__, 'avb_create_db_table' );


// 2. SECURITY CHECK: NONCE VALIDATION FUNCTION (Paso 2.4)
function avb_validate_rest_nonce( $request ) {
    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        avb_log_event( 'Security violation: Invalid Nonce token received for REST API.', 'security-alert' );
        return new WP_Error( 'rest_forbidden', __( 'Invalid nonce token. Access denied for security.', 'avb-core' ), array( 'status' => 401 ) );
    }
    return true;
}

// 3. LOGGING FUNCTION (API Audit and Debugging - Paso 2.5)
function avb_log_event( $message, $level = 'info' ) {
    if ( ! is_string( $message ) ) {
        $message = print_r( $message, true );
    }

    $log_prefix = strtoupper( $level ) . ' | AVB-AUDIT | ';
    error_log( $log_prefix . $message );
}


// 4. REGISTER CUSTOM REST ENDPOINT (Paso 2.2)
function avb_register_rest_endpoint() {
    register_rest_route( 'avb/v1', '/submit-ancestor-data', array(
        'methods' => 'POST', 
        'callback' => 'avb_handle_ancestor_data',
        'permission_callback' => 'avb_validate_rest_nonce', 
    ) );
}
add_action( 'rest_api_init', 'avb_register_rest_endpoint' );


// 5. API CALLBACK FUNCTION (Logic to insert data)
function avb_handle_ancestor_data( $request ) {
    $params = $request->get_params();

    if ( empty( $params['client_email'] ) || empty( $params['name'] ) ) {
        avb_log_event( 'API request rejected: Missing client_email or name.', 'error' );
        return new WP_Error( 'missing_data', 'Required fields are missing.', array( 'status' => 400 ) );
    }

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
        $webhook_status = avb_send_webhook_to_crm( $params ); 
        avb_log_event( 'Data ID: ' . $wpdb->insert_id . ' | WebHook Status: ' . $webhook_status['status'] . ' | Email: ' . $params['client_email'], 'success' );

        return new WP_REST_Response( array( 
            'message' => 'Ancestor data saved successfully!', 
            'id' => $wpdb->insert_id,
            'webhook_status' => $webhook_status['status']
        ), 200 );
    } else {
        avb_log_event( 'DB INSERT FAILED for Email: ' . $params['client_email'] . ' | Error: ' . $wpdb->last_error, 'db-error' );
        return new WP_Error( 'db_error', 'Could not save data to database.', array( 'status' => 500 ) );
    }
}


// 6. WEBHOOK FUNCTION (SIMULATE CRM TRANSFER - Paso 2.3)
function avb_send_webhook_to_crm( $ancestor_data ) {
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
        avb_log_event('Webhook failed to send to CRM. Error: ' . $response->get_error_message(), 'webhook-fail');
        return array('status' => 'webhook_error'); 
    } else {
        return array('status' => 'success');
    }
}


// 7. CHAT WIDGET INJECTION (Paso 3.1 - CORRECCIÓN CRÍTICA HEREDOC)
function avb_inject_chat_widget_html() {
    // CORRECCIÓN CRÍTICA: Usamos sintaxis HEREDOC para evitar errores de sintaxis en el HTML.
    $html = <<<HTML
<div id="avb-floating-button" title="Open Ancestral Viability Chat">💬</div>
<div id="avb-chat-container">
    <div id="avb-header">ANCESTRAL VIABILITY BOT</div>
    <div id="avb-dialogue-area"></div>
    <div id="avb-input-container">
        <input type="text" id="avb-input" placeholder="Type your message...">
        <button id="avb-send">Send</button>
    </div>
</div>
HTML;
    echo $html;
}
add_action( 'wp_footer', 'avb_inject_chat_widget_html' );