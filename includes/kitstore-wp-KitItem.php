<?php
require_once "BarcodeUtils.php";

class KitItem
{
    public static $returnList = '';            // BarcodeUtils, note to pass barcode property
    public static $borrowedList = '';
    public $barcode;
    public $onLoan = 0;
    public $currentProblem = false;
    private $itemType;
    private $parentItem = null;
    private $children = array();
    private $enteredService;
    private $retired = null;
    private $problems = array();

    public function __construct($byBarcode = null, $kit_type = null, $parent = null)
    {
        if ($kit_type != null) {
            // create a brand new KitItem
            // only possible with information about the type of kit

            if ($byBarcode == null) {
                // create a barcode
                $kit_barcode = new BarcodeUtils();
                $kit_barcode->saveBarcode();
                $this->setBarcode($kit_barcode);

            } else {
                // set barcode value as passed
                $this->setBarcode($byBarcode);
            }
            $this->setKitType($kit_type);
            if ($parent != null) {
                $this->setParent($parent);
            }
            $this->setEnteredService();
            $this->checkLoanStatus();
            $this->saveKitItem();
        } else {
            /*
             * try to retrieve data about kitItem from database
             * using barcode
             */
            $this->setBarcode($byBarcode);
            $this->getKitItem();
        }
    }

    private function setBarcode($bcSetter)
    {
        // save as barcode utils, check if passed as int and create if necessary
        if (is_a($bcSetter, "BarcodeUtils")) {
            $this->barcode = $bcSetter;
        } else if (is_numeric($bcSetter)) {
            $this->barcode = new BarcodeUtils($bcSetter);
        } else {
            die('Could not set barcode with value: ' . print_r($bcSetter) . ' type = ' . gettype($bcSetter));
        }
    }

    private function setKitType($ktSetter)
    {
        if (is_numeric($ktSetter)) {
            $this->itemType = $ktSetter;
        } else {
            global $wpdb;
            $this->itemType = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id from {$wpdb->prefix}tTypes
                    WHERE type = %s",
                    $ktSetter)
                );
        
        /*
            include_once dbConnection::DB_CONFIG;
            $mysqli_conn = db_connect();
            $stmnt = $mysqli_conn->prepare("SELECT id from tTypes WHERE type = ?");
            $stmnt->bind_param("s", $ktSetter);
            $stmnt->execute();
            $stmnt->bind_result($kt_id);
            $stmnt->fetch();
            $this->itemType = $kt_id;
            $stmnt->close();
            $mysqli_conn->close();
        */ 
        }
    }

    private function setParent($pSetter)
    {
        if (is_a($pSetter, "BarcodeUtils")) {
            $this->parentItem = $pSetter;
        } else if (is_numeric($pSetter)) {
            $this->parentItem = new BarcodeUtils($pSetter);
        } else {
            die("Could not create parentItem with value : " . var_dump($pSetter) . gettype($pSetter));
        }
    }

    private function setEnteredService()
    {
        global $wpdb;
        $es_res = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT entered_service as esdate 
                FROM {$wpdb->prefix}tKit
                WHERE barcode = %d",
                $this->barcode->barcode)
            );
        if (! empty($es_res)) {
            $this->enteredService = $es_res->esdate;
        } else {
            $this->enteredService = date("Y-m-d G:i:s");
        }  
        
        /*
        include_once dbConnection::DB_CONFIG;
        $mysqli_conn = db_connect();
        $stmnt = $mysqli_conn->prepare("SELECT entered_service from tKit WHERE barcode = ?");
        $stmnt->bind_param('d', $this->barcode->barcode);
        $stmnt->execute();
        if ($stmnt->num_rows()) {
            $stmnt->bind_result($entry_date);
            $stmnt->fetch();
            $this->enteredService = $entry_date;
        } else {
            $this->enteredService = date("Y-m-d G:i:s");
        }
        $stmnt->close();
        $mysqli_conn->close();
        */
    }

    private function checkLoanStatus()
    {
        global $wpdb;
        $loans = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT count(id) as lcount 
                FROM {$wpdb->prefix}tLoans
                WHERE item = %d AND time_in IS NULL",
                $this->barcode->barcode)
            );
        if ($loans->lcount) {
            $this->onLoan = 1;
        } else {
            $this->onLoan = 0;
        }
        
        /* 
        include_once dbConnection::DB_CONFIG;
        $db_conn = db_connect();
        $stmnt = $db_conn->prepare("SELECT count(id) from tLoans
            WHERE item=? and time_in IS NULL");
        $stmnt->bind_param("i", $this->getBarcode()->getBarcode());
        if (!$stmnt->execute()) {
            die('problem with onLoan statement : ' . $stmnt->error);
        }
        //print_r($stmnt->num_rows);
        $stmnt->bind_result($count);
        $stmnt->fetch();
        if ($count) {
            $this->onLoan = 1;
        } else {
            $this->onLoan = 0;
        }
        $stmnt->close();
        $db_conn->close();
        */
    }

    public function getBarcode()
    {
        return $this->barcode;
    }

    private function saveKitItem()
    {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}tKit 
            (barcode, parent_item, type, entered_service, retired)
            VALUES (%d,%d,%d,%s,%s)
            ON DUPLICATE KEY UPDATE
            parent_item = %d, type = %d, entered_service = %s, retired = %s",
            array($this->barcode->barcode, $this->parentItem->barcode,
            $this->itemType, $this->enteredService, $this->retired,
            $this->parentItem->barcode, $this->itemType, $this->enteredService,
            $this->retired)));
        /*
        include_once dbConnection::DB_CONFIG;
        $mysqli_conn = db_connect();
        $stmnt = $mysqli_conn->prepare("INSERT INTO tKit 
            (barcode, parent_item, type, entered_service, retired)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
            parent_item = ?, type = ?, entered_service = ?, retired = ?");
        $stmnt->bind_param("dddssddss", $this->barcode->barcode, $this->parentItem->barcode,
            $this->itemType, $this->enteredService, $this->retired,
            $this->parentItem->barcode, $this->itemType, $this->enteredService,
            $this->retired);
        if (!$stmnt->execute()) {
            die("Couldn't save KitItem : " . $stmnt->error);
        }
        */
    }

    private function getKitItem()
    {
        global $wpdb;
        $kitdata = $wpdb->get_row(
            $wpdb->prepare("SELECT parent_item, 
            type, entered_service, retired 
            FROM {$wpdb->prefix}tKit
            WHERE barcode=%d",
            $this->barcode->barcode));
        $this->parentItem = $kitdata->parent_item;
        $this->itemType = $kitdata->type;
        $this->enteredService = $kitdata->entered_service;
        $this->retired = $kitdata->retired;
        
        $kitdata = $wpdb->get_results(
            $wpdb->prepare("SELECT barcode 
            FROM {$wpdb->prefix}tKit
            WHERE parent_item=%d",
            $this->barcode->barcode));
        foreach ($kitdata as $kd) {
            $this->children[] = new KitItem($kd->barcode);
        } 
        
        $kitdata = $wpdb->get_results(
            $wpdb->prepare("SELECT id 
            FROM {$wpdb->prefix}tProblems
            WHERE item=%d AND time_fixed IS NULL",
            $this->barcode->barcode));
        foreach ($kitdata as $kd) {
            $this->problems[] = $kd->id;
            $this->currentProblem = true;
        }
        $this->checkLoanStatus();
        /*
        include_once dbConnection::DB_CONFIG;
        $mysqli_conn = db_connect();
        $stmnt = $mysqli_conn->prepare("SELECT parent_item, 
            type, entered_service, retired from tKit WHERE barcode=?");
        $stmnt->bind_param("d", $this->barcode->barcode);
        $stmnt->execute();
        $stmnt->bind_result($this->parentItem, $this->itemType,
            $this->enteredService, $this->retired);
        $stmnt->fetch();
        $stmnt->close();
        $stmnt = $mysqli_conn->prepare("SELECT barcode from tKit 
            WHERE parent_item=?");
        $stmnt->bind_param("d", $this->barcode->barcode);
        $stmnt->execute();
        $stmnt->bind_result($child_barcode);
        while ($stmnt->fetch()) {
            // echo("found child barcode : ". $child_barcode . "\r\n");
            $this->children[] = new KitItem($child_barcode);
        }
        $stmnt->close();

			$stmnt = $mysqli_conn->prepare(
				"SELECT id from tProblems
				WHERE item=? AND time_fixed IS NULL");
			$stmnt->bind_param("i", $this->barcode->barcode);
			$stmnt->execute();
			$stmnt->bind_result($prid);
			while ($stmnt->fetch()) {
				$this->problems[] = $prid;
				$this->currentProblem = true;
			}
			$stmnt->close();
			        $mysqli_conn->close();
        $this->checkLoanStatus();
        */
    }

    public static function getLatestReturns()
    {
        return self::$returnList;
    }

    public static function getLatestLoans()
    {
        return self::$borrowedList;
    }

    public function update()
    {
        $this->getKitItem();
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function signOut($kUser)
    {
        if (!$kUser) return false;
        // check all descendents
        if (!$this->isAvailable()) return false;    // this item isn't available to sign out

        // if we're here then this and all child items should be available to sign out.
        $this->signOutChecked($kUser);
        return true;
    }

    public function isAvailable()
    {
        foreach ($this->children as $childItem) {
            if (!$childItem->isAvailable()) return false;   // this child item isn't available to sign out
        }
        // have checked the descendents, so is this item OK to sign out?
        return !$this->isOnLoan() && !$this->currentProblem;
    }

    public function isOnLoan()
    {
        return $this->onLoan;
    }
  
  	public function getCurrentBorrower()
    {
    	    if ($this->isOnLoan()) {
        	    global $wpdb;
        	    $user = $wpdb->get_row(
        	        $wpdb->prepare("SELECT user 
        	        FROM {$wpdb->prefix}tLoans 
        	        WHERE item = %d AND time_in IS NULL",
        	        $this->barcode->barcode));
            $kit_user = new WP_User($user->user);
            if ($kit_user->exists()) {
                return $kit_user->display_name;
            } else {
                return "-";
            } 
              
              /*
              $mysqli = db_connect();
              $stmnt = $mysqli->prepare("
              	SELECT user from tLoans WHERE item = ? and time_in IS NULL
              ");
              $stmnt->bind_param('i', $this->barcode->getBarcode());
              if (!$stmnt->execute()) {
                die('problem with get current borrower statement : ' . $stmnt->error);
              }
              $stmnt->bind_result($k_user);
              $stmnt->fetch();
              $kit_user = new KitUser($k_user);
              
              return $kit_user->getName();
              $stmnt->close();
              $mysqli->close();
              */
        } else {
          return "-";
        }
    }

    private function signOutChecked($kUser)
    {
        global $wpdb;
        $inserted = $wpdb->query(
            $wpdb->prepare("INSERT INTO {$wpdb->prefix}tLoans (item, user)
                VALUES (%d, %d)",
                array($this->barcode->barcode, kUser)));
        if ($inserted) {
            $this->onLoan = 1;
            self::$borrowedList .= $this->getBarcode()->getBarcode() . ", ";
        } 
        /*
        $mysqli = db_connect();
        $statemnt = $mysqli->prepare(
            "INSERT INTO tLoans (item, user)
                VALUES (?, ?)");
        $statemnt->bind_param("ii", $this->getBarcode()->getBarcode(), $kUser);
        if ($statemnt->execute()) {
            $this->onLoan = 1;
            self::$borrowedList .= $this->getBarcode()->getBarcode() . ", ";
        }
        $statemnt->close();
        $mysqli->close();
        */
        
        foreach ($this->children as $kChild) {
            $kChild->signOutChecked($kUser);
        }
    }

    public function signIn($codes)
    {
        // make sure all descendents of this item are available for check in
        // @params: $codes as string list, space separated.

        self::$returnList = '';

        /*
           only bother signing in if this item has no parent
           as the highest in a hierarchy will verify then check in
           all descendents.
        */
        // "Warning: dont need to check this one in, not on loan or has a parent item";
        if (!is_null($this->parentItem)) {
            throw new Exception("Couldn't sign in, this item (" . $this->getBarcode()->getBarcode() . ") belongs to item " . $this->parentItem . ".");
        }
        if (!$this->onLoan) {
            throw new Exception("Couldn't sign in, this item (" . $this->getBarcode()->getBarcode() . ") is not on loan.");
        }

        $codeArr = explode(" ", trim($codes));
        $descends = $this->getDescendents();
        array_push($descends, $this->getBarcode()->getBarcode());
        $missing = array_diff($descends, $codeArr);
        if (!$missing) {
            // all descendents are being signed in, just do them now, as have checked already
            global $wpdb;
            foreach ($descends as $desc) {
                $success = $wpdb->query(
                                $wpdb->prepare("UPDATE tLoans SET time_in = NOW()
                                WHERE item = %d AND time_in IS NULL", $desc));
                if ($success) {
                    self::$returnList .= "Returned " . $returnedBc . "<br>";
                } 
            } 
            return $descends;
        

            /*
            $mysqli = db_connect();
            // disable autocommit 
            $mysqli->autocommit(FALSE);
            $stmnt = $mysqli->prepare("UPDATE tLoans SET time_in = NOW() WHERE item = ? AND time_in IS NULL");
            $stmnt->bind_param("i", $returnedBc);
            foreach ($descends as $desc) {
                $returnedBc = $desc;
                if (!$stmnt->execute()) {
                    $mysqli->rollback();
                    $stmnt->close();
                    $mysqli->close();
                    throw new Exception("Error updating database");
                }
                if (!$stmnt->affected_rows) {
                    $mysqli->rollback();
                    $stmnt->close();
                    $mysqli->close();
                    throw new Exception("Error : code not signed in - " . $returnedBc);
                } else {
                    self::$returnList .= "Returned " . $returnedBc . "<br>";
                }
            }
            // commit update transaction
            $mysqli->commit();
            $stmnt->close();
            $mysqli->close();
            return $descends;
            */
            
        } else {
            // something's missing from the sign in list.
            throw new Exception("Error : Cannot sign kit item " . $this->getBarcode()->getBarcode() .
                " back in due to missing components : " . join(", ", $missing));
        }
        // return array_diff($codeArr, $descends);
    }

    protected function getDescendents()
    {
        $desc = array();
        if (empty($this->children)) return $desc;
        foreach ($this->children as $childBc) {
            array_push($desc, $childBc->getBarcode()->getBarcode());
            $grandchildren = $childBc->getDescendents();
            if (!empty($grandchildren)) array_push($desc, $grandchildren);
        }
        return $desc;
    }

    public function getProblemDetail($current=true)
    {
        global $wpdb;
        $dbdata = array();
        foreach ($this->problems as $problem) {
            $results = $wpdb->get_row(
                        $wpdb->prepare(
            "SELECT problem, time_logged, critical
            FROM {$wpdb->prefix}tProblems
            WHERE id=%d" + ($current ? "AND time_fixed IS NULL" : "" ),
            $problem));
            $dbdata[] = array(
                'id' => strval($problem), 
                'description' => $results->problem, 
                'time' => $results->time_logged, 
                'critical' => $results->critical);
        }
        
        
        /*
        $mysqli = db_connect();
        $stmnt = $mysqli->prepare(
            "SELECT problem, time_logged, critical from tProblems
            WHERE id=?" + ($current ? "AND time_fixed IS NULL" : "" ));
        $dbdata = array();

        foreach ($this->problems as $problem) {
            $stmnt->bind_param("i", $problem);
            $stmnt->execute();
            $stmnt->bind_result($pDescription, $pLogTime, $pCritical);
            $stmnt->fetch();
            $dbdata[] = array('id' => strval($problem), 'description' => $pDescription, 'time' => $pLogTime, 'critical' => $pCritical);
        }
        $stmnt->close();
        $mysqli->close();
        */
        
        return json_encode($dbdata);
    }

	public function setProblem($desc, $crit=0) {
	    global $wpdb;
	    $inserted = $wpdb->query(
	                $wpdb->prepare("
	                INSERT INTO {$wpdb->prefix}tProblems(item, problem, time_logged, critical)
	                VALUES (%d, %s , NOW(), %d)", 
	                array($this->barcode->barcode, $desc, $crit)));
	                
        if ($inserted) {
            $this->current_problem = true;
			$this->problems[] = $mysqli->insert_id;
        } else {
            die("Couldn't save problem : " . $wpdb->last_error);
        } 
	    /*
		$mysqli = db_connect();
		$stmnt = $mysqli->prepare(
			"INSERT into tProblems(item, problem, time_logged, critical)
			VALUES (?, ? , NOW(), ?)");
		$stmnt->bind_param("isi", $this->barcode->barcode, $desc, $crit);
		if (!$stmnt->execute()) {
            die("Couldn't save problem : " . $stmnt->error);
      } else {
			$this->current_problem = true;
			$this->problems[] = $mysqli->insert_id;
		}
		*/
	
	}
}
