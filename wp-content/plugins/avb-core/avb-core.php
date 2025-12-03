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