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
<?php

global $kwp_db_version;
$kwp_db_version = '1.0';

function kwp_install() {
	global $wpdb;
	global $kwp_db_version;

	$table_name_barcodes = $wpdb->prefix . 'tBarcodes';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name_barcodes (
	barcode int(8) NOT NULL
	PRIMARY KEY  (barcode),
	UNIQUE KEY barcode (barcode)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

/*
might not need groups table if implement a user tagging system

CREATE TABLE IF NOT EXISTS `tGroups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exped_year` int(4) NOT NULL,
  `award_level` varchar(10) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Level of Award, or Staff',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/

	$table_name_kit = $wpdb->prefix . 'tKit';
	
	$sql ="CREATE TABLE $table_name_kit (
	barcode int(11) NOT NULL,
	parent_item int(11) DEFAULT NULL,
	type int(11) NOT NULL,
	entered_service datetime NOT NULL,
	retired datetime DEFAULT NULL,
	PRIMARY KEY  (barcode),
	KEY tkit_fk1 (parent_item),
	KEY tkit_fk2 (type)
	) $charset_collate;";
	dbDelta($sql);
/*
-- --------------------------------------------------------

--
-- Table structure for table `tLoans`
--

CREATE TABLE IF NOT EXISTS `tLoans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` int(11) NOT NULL,
  `user` int(11) NOT NULL,
  `time_out` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `time_in` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tLoans_fk0` (`item`),
  KEY `tLoans_fk1` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tLogin`
--

CREATE TABLE IF NOT EXISTS `tLogin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oauth_provider` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'google',
  `oauth_uid` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `time_created` datetime NOT NULL,
  `time_modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `oauth_uid` (`oauth_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tProblems`
--

CREATE TABLE IF NOT EXISTS `tProblems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` int(11) NOT NULL,
  `problem` varchar(1022) COLLATE utf8_unicode_ci NOT NULL,
  `time_logged` datetime NOT NULL,
  `time_fixed` datetime DEFAULT NULL,
  `critical` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `tProblems_fk0` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tTypes`
--

CREATE TABLE IF NOT EXISTS `tTypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('Tent','Flysheet','Inner','Poles','Pegs','Stove','Rucksack','Compass','Map') COLLATE utf8_unicode_ci NOT NULL,
  `brand` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `parent_type` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tUsers`
--

CREATE TABLE IF NOT EXISTS `tUsers` (
  `barcode` int(11) NOT NULL,
  `first_name` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `initial` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `group` int(11) NOT NULL,
  PRIMARY KEY (`barcode`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `tUsers_fk1` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tKit`
--
ALTER TABLE `tKit`
  ADD CONSTRAINT `tKit_fk0` FOREIGN KEY (`barcode`) REFERENCES `tBarcodes` (`barcode`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tKit_fk2` FOREIGN KEY (`type`) REFERENCES `tTypes` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tLoans`
--
ALTER TABLE `tLoans`
  ADD CONSTRAINT `tLoans_fk0` FOREIGN KEY (`item`) REFERENCES `tKit` (`barcode`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tLoans_fk1` FOREIGN KEY (`user`) REFERENCES `tUsers` (`barcode`) ON UPDATE CASCADE;

--
-- Constraints for table `tProblems`
--
ALTER TABLE `tProblems`
  ADD CONSTRAINT `tProblems_fk0` FOREIGN KEY (`item`) REFERENCES `tKit` (`barcode`);

--
-- Constraints for table `tUsers`
--
ALTER TABLE `tUsers`
  ADD CONSTRAINT `tUsers_fk0` FOREIGN KEY (`barcode`) REFERENCES `tBarcodes` (`barcode`),
  ADD CONSTRAINT `tUsers_fk1` FOREIGN KEY (`group`) REFERENCES `tGroups` (`id`);
COMMIT;

	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
*/
	add_option( 'kwp_db_version', $kwp_db_version );
}	

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