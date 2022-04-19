<?php

/**
 * Plugin Name: Kitstore WP
 * Plugin URI: richardshelswell.co.uk/kitstore-wp
 * Version: 1.0
 * Description: Inventory system plugin for Wordpress. Barcode tracking of items, sign kit out to a user, and back in to store. Keeps track of repairs needed, blocking loans if necessary. View kit by type, availability and user.
 * Author: Rich Shelswell
 * Author URI: http://www.richardshelswell.co.uk
 * License: Apache-2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
**/


function kwp_activate() {
	global $wpdb;
	global $kwp_db_version;
	$kwp_db_version = "1.0";

	$table_name_barcodes = $wpdb->prefix . 'tBarcodes';
	$table_name_kit = $wpdb->prefix . 'tKit';
	$table_name_loans = $wpdb->prefix . 'tLoans';
	$table_name_problems = $wpdb->prefix . 'tProblems';
	$table_name_types = $wpdb->prefix . 'tTypes';	

	$charset_collate = $wpdb->get_charset_collate();
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	$sql = "CREATE TABLE IF NOT EXISTS $table_name_barcodes (
	barcode int(8) NOT NULL,
	PRIMARY KEY  (barcode)
	) $charset_collate;";
	
	dbDelta($wpdb->prepare($sql));
	
	$sql = "CREATE TABLE IF NOT EXISTS $table_name_kit (
	barcode int(11) NOT NULL,
	parent_item int(11) DEFAULT NULL,
	type int(11) NOT NULL,
	entered_service datetime NOT NULL,
	retired datetime DEFAULT NULL,
	PRIMARY KEY  (barcode)
	) $charset_collate;";

	dbDelta($wpdb->prepare($sql));
	
	$sql = "CREATE TABLE IF NOT EXISTS $table_name_loans (
	id int(11) NOT NULL AUTO_INCREMENT,
	item int(11) NOT NULL,
	user int(11) NOT NULL,
	time_out datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	time_in datetime DEFAULT NULL,
	PRIMARY KEY  (id)
	) $charset_collate;";
	
	dbDelta($wpdb->prepare($sql));
	
	$sql = "	CREATE TABLE IF NOT EXISTS $table_name_problems (
	id int(11) NOT NULL AUTO_INCREMENT,
	item int(11) NOT NULL,
	problem varchar(1022) NOT NULL,
	time_logged datetime NOT NULL,
	time_fixed datetime DEFAULT NULL,
	critical tinyint(1) NOT NULL DEFAULT '1',
	PRIMARY KEY  (id)
	) $charset_collate;";
	
	dbDelta($wpdb->prepare($sql));
	
	$sql = "	CREATE TABLE IF NOT EXISTS $table_name_types (
	id int(11) NOT NULL AUTO_INCREMENT,
	type enum('Tent','Flysheet','Inner','Poles','Pegs','Stove','Rucksack','Compass','Map','Rollmat') NOT NULL,
	brand varchar(255) NOT NULL,
	model varchar(255) NOT NULL,
	description varchar(255) NOT NULL,
	parent_type int(11) DEFAULT NULL,
	PRIMARY KEY  (id)
	) $charset_collate;";
	dbDelta($wpdb->prepare($sql));
	
	add_option( 'kwp_db_version', $kwp_db_version );
}

register_activation_hook( __FILE__, 'kwp_activate' );

function kwp_deactivate() {

}

register_deactivation_hook( __FILE__, 'kwp_deactivate' );


/**
 *	shortcodes for form inclusions
 */
 
/**
 * [kit_sign_out] returns the sign out form as html string.
 * @return string html with form for sign out
*/

add_shortcode( 'kit_sign_out', 'kwp_kit_sign_out' );

function kwp_init(){
	function kwp_kit_sign_out() {
		return getdate()['year'];
	}
}
add_action('init', 'kwp_init');

?>