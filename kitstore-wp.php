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
        add_shortcode( 'kit_sign_out_message', array( 'Kitstore', 'sign_out_message' ) );
        
        // Add the group field to user profile editing screen.
		add_action(
		    'edit_user_profile',
		    array('Kitstore', 'usermeta_form_field_group')
		);
		  
		  
		// Add the group save action to user profile editing screen update.
		add_action(
		    'edit_user_profile_update',
		    array('Kitstore', 'usermeta_form_field_group_update')
		);
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

    public static function sign_out_message() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
		require_once( plugin_dir_path(__FILE__) . 'includes/kitstore-wp-KitItem.php');
        
        // process items for sign out
        $kUser = filter_var($_POST['user-choice'], FILTER_VALIDATE_INT);
	    $userObj = new WP_User($kUser); 
        
        if (! $kUser || ! $userObj->exists()) {
    			$message = "Error: invalid user id selected";
		} elseif (!preg_match("/^([\d]{8}\s*)+$/", trim($_POST['item_codes']))) {
        		$message = "Error: invalid kit barcodes supplied";
        } else {
			
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

	        $message = "Signed out items with barcodes : " . KitItem::getLatestLoans() . " to user : " . $userObj->display_name . "<br>";
	        if (!empty($fail_barcodes)) {
	            $message .= "These barcodes failed to sign out, please resolve problems : " . join(", ", $fail_barcodes) . "<br>";
	        }
		} 
        if (!empty($message)) {
        	    $infobox = <<<HTML
<!-- wp:kadence/infobox {"uniqueID":"_f25390-0e","hAlign":"left","containerBackground":"#ffffff","containerHoverBackground":"#ffffff","containerBorderWidth":[5,5,5,5],"containerBorderRadius":20,"containerPadding":[24,24,24,24],"mediaAlign":"left","mediaImage":[{"url":"","id":"","alt":"","width":"","height":"","maxWidth":100,"hoverAnimation":"none","flipUrl":"","flipId":"","flipAlt":"","flipWidth":"","flipHeight":"","subtype":"","flipSubtype":""}],"mediaIcon":[{"icon":"fe_alertCircle","size":50,"width":2,"title":"Alert","color":"#64a56a","hoverColor":"#444444","hoverAnimation":"none","flipIcon":""}],"mediaStyle":[{"background":"#ffffff","hoverBackground":"#ffffff","border":"#eeeeee","hoverBorder":"#eeeeee","borderRadius":120,"borderWidth":[5,5,5,5],"padding":[20,20,20,20],"margin":[0,20,0,0]}],"titleFont":[{"level":3,"size":["","",""],"sizeType":"px","lineHeight":["","",""],"lineType":"px","letterSpacing":"","textTransform":"","family":"Ubuntu","google":true,"style":"normal","weight":"400","variant":"regular","subset":"latin","loadGoogle":true,"padding":[0,0,0,0],"paddingControl":"linked","margin":[5,0,10,0],"marginControl":"individual"}],"textFont":[{"size":["","",""],"sizeType":"px","lineHeight":["","",""],"lineType":"px","letterSpacing":"","family":"Ubuntu","google":true,"style":"normal","weight":"400","variant":"regular","subset":"latin","loadGoogle":true,"textTransform":""}],"containerMargin":["","","",""]} -->
<div id="kt-info-box_f25390-0e" class="wp-block-kadence-infobox"><a class="kt-blocks-info-box-link-wrap info-box-link kt-blocks-info-box-media-align-left kt-info-halign-left"><div class="kt-blocks-info-box-media-container"><div class="kt-blocks-info-box-media kt-info-media-animate-none"><div class="kadence-info-box-icon-container kt-info-icon-animate-none"><div class="kadence-info-box-icon-inner-container"><span style="display:block;justify-content:center;align-items:center" class="kt-info-svg-icon kt-info-svg-icon-fe_alertCircle"><svg style="display:inline-block;vertical-align:middle" viewbox="0 0 24 24" height="50" width="50" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><title>Alert</title><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12" y2="16"></line></svg></span></div></div></div></div><div class="kt-infobox-textcontent"><h3 class="kt-blocks-info-box-title">Form submitted</h3><p class="kt-blocks-info-box-text">$message</p></div></a></div>
<!-- /wp:kadence/infobox --> 
HTML;
        		return $infobox;
		} else {
			return;
		}	
    }

    public static function sign_out_ui() {
        
        $output = <<<HTML
        <!-- wp:shortcode -->
        [kit_sign_out_message]
        <!-- /wp:shortcode -->
        <!-- wp:paragraph -->
        <p>Choose user from list and scan barcode(s) 
        for any equipment borrowed.</p>
        <!-- /wp:paragraph -->
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
		$pg = get_page_by_title("Sign Out");
		if (!empty($pg)) {
			$soid = $pg->ID;
		} else {
			$soid = 0;
		}

		$signout_page = array(
			'post_type'     => 'page',
			'ID'		=> $soid,
			'post_status'   => 'private',
			'post_title'    => 'Sign Out',
			'post_content'  => $output);
		wp_insert_post($signout_page);
    }
    
    /**
 	 * add group field to user editing screen.
	 *
	 * @param $user WP_User user object
	 */
	public static function usermeta_form_field_group( $user )
	{
	    ?>
	    <h3>DofE group</h3>
	    <table class="form-table">
	        <tr>
	            <th>
	                <label for="dofegroup">Group</label>
	            </th>
	            <td>
	                <input type="text"
	                       class="regular-text ltr"
	                       id="dofegroup"
	                       name="dofegroup"
	                       value="<?= esc_attr( get_user_meta( $user->ID, 'dofegroup', true ) ) ?>"
	                       title="Please use Staff or Level Year format (e.g. Bronze 2022)."
	                       pattern="(Staff|((Bronze|Silver|Gold) (19[0-9][0-9]|20[0-9][0-9])))">
	                <p class="description">
	                    Choose group for user.
	                </p>
	            </td>
	        </tr>
	    </table>
	    <?php
	}
	  
	/**
	 * The save action.
	 *
	 * @param $user_id int the ID of the current user.
	 *
	 * @return bool Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public static function usermeta_form_field_group_update( $user_id )
	{
	    // check that the current user have the capability to edit the $user_id
	    if ( ! current_user_can( 'edit_user', $user_id ) ) {
	        return false;
	    }
	  
	    // create/update user meta for the $user_id
	    return update_user_meta(
	        $user_id,
	        'dofegroup',
	        $_POST['dofegroup']
	    );
	}
	 
}

$kwp_plugin = new Kitstore;


?>