<?php

/**
 * Plugin Name: Kitstore WP
 * Plugin URI: richardshelswell.co.uk/kitstore-wp
 * Version: 1.0.0
 * Description: Inventory system plugin for Wordpress. Barcode tracking of items, sign kit out to a user, and back in to store. Keeps track of repairs needed, blocking loans if necessary. View kit by type, availability and user.
 * Author: Rich Shelswell
 * Author URI: http://www.richardshelswell.co.uk
 * License: Apache-2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0
**/

class Kitstore {
	/**
     * Constructor
     */
    public function __construct() {
        $this->setup_actions();
    }
    
    /**
     * Setting up Hooks
     */
    public function setup_actions() {
        //Main plugin hooks
        register_activation_hook( __FILE__, array( 'Kitstore', 'activate' ) );
        register_deactivation_hook( __FILE__, array( 'Kitstore', 'deactivate' ) );
        
        //Shortcode hooks
        add_shortcode( 'kit_sign_out_message', array( 'Kitstore', 'kwp_kit_sign_out_message' ) );
    }

	public static function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) return;
		
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
		
		Kitstore::sign_out_ui();
		
	}

	public static function deactivate() {
	
	}

    function sign_out_message() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        // process items for sign out
        $kUser = $_POST['user-choice'];
        $kItems = explode(" ", trim($_POST['item_codes']));
        $used_barcodes = array();
        $fail_barcodes = array();
        foreach ($kItems as $bcString) {
            $ki = new KitItem($bcString);
            if ($ki->signOut($kUser)) {
                array_push($used_barcodes, $bcString);
            } else {
                array_push($fail_barcodes, $bcString);
            }
        }
        
        $userObj = new KitUser($kUser);
        $message = "Signed out items with barcodes : " . KitItem::getLatestLoans() . " to user : " . $userObj->getName() . "<br>";
        if (!empty($fail_barcodes)) {
            $message .= "These barcodes failed to sign out, please resolve problems : " . join(", ", $fail_barcodes) . "<br>";
        }
        if (!empty($message)) {
        	    $infobox = <<<HTML
            <div class="alert alert-info" role="alert">
                $message
            </div>
HTML;
        		return $infobox;
		}	
    }

    public static function sign_out_ui() {
        
        $output = <<<HTML
        [kit_sign_out_message]
        <p>Choose user from list and scan barcode(s) for any equipment
            borrowed.</p>
        <form action="" method="post">
            <div id="group_select">
                <label for="group-selector">Choose user's group</label>
                <select class="form-control" id="group-selector" name="group-selector"
                        onchange="show_participant_choices(1)">
                    <option value="0">All users</option>
                </select>
            </div>
            <div id="participant-input">

            </div>
            <div class="form-group">
                <label for="item_codes">Items to sign out</label>
                <div class="input-group">
            <textarea class="form-control" id="item_codes" name="item_codes"
                      rows="5" placeholder="Tap the button to scan an EAN..."></textarea>
                    <button class="btn btn-outline-primary" data-target="#livestream_scanner" data-toggle="modal"
                            type="button" id="btn_multi_scan">
                        Scan
                    </button>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Submit</button>
        </form>

HTML;

		$signout_page = array(
			'post_type'     => 'page',
			'post_ID'		=> post_exists( "Sign Out",'','page',''),
			'post_status'   => 'draft',
			'post_title'    => 'Sign Out',
			'post_content'  => $output);
		wp_insert_post($signout_page);
    }
}

$kwp_plugin = new Kitstore;

?>