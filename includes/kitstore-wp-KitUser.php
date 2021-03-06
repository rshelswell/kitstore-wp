<?php
require_once "interfaces.php";
require_once "BarcodeUtils.php";

class KitUser extends WP_User
{
    /**
     * remove items duplicated 
        public $firstName = null;
        public $lastName = null;
        public $barcode = null;
    */
    public $awardLevel = null;
    public $expedYear = null;
    protected $group = null;
    protected $loans = array();

    public function __construct($bcIdent = null)
    {
        if ($bcIdent !== null) {
            // get ready to find information from database
            try {
                $this->setBarcode($bcIdent);
            } catch (\http\Exception\InvalidArgumentException $excpt) {
                echo "Failed to create KitUser : " . $excpt;
                exit();
            }
            $this->getDataByBarcode();
        }
    }

    protected function getDataByBarcode()
    {
        $dbconn = db_connect();
        $stmnt = $dbconn->prepare(
            "SELECT tU.first_name, tU.initial, tU.`group`, tG.award_level, tG.exped_year,
                GROUP_CONCAT(tL.item SEPARATOR ',')
            FROM tUsers tU LEFT JOIN tGroups tG on tU.`group` = tG.id LEFT JOIN tLoans tL on tU.barcode = tL.user 
            WHERE tU.barcode = ? AND tL.time_in IS NULL
            LIMIT 1"
        );
        $stmnt->bind_param("d", $this->barcode);
        $stmnt->execute();
        $stmnt->bind_result($first_name, $last_name, $group, $award_level, $exped_year, $loan_items);
        $stmnt->fetch();
        $this->setFirstName($first_name);
        $this->setLastName($last_name);
        $this->setGroup($group);
        $this->setAwardLevel($award_level);
        $this->setExpedYear($exped_year);
        // next handle the loans
        $this->loans = explode(',', $loan_items);
    }

    function setGroup($newGroup)
    {
        $this->group = $newGroup;
    }

    public function getBarcode()
    {
        return $this->barcode;
    }

    public function setBarcode($suppliedBarcode)
    {
        if (is_a($suppliedBarcode, "BarcodeUtils")) {
            $this->barcode = $suppliedBarcode->getBarcode();
        } else {
            try {
                $suppliedBarcode = new BarcodeUtils($suppliedBarcode);
                $this->setBarcode($suppliedBarcode);
            } catch (\http\Exception\InvalidArgumentException $bad_code) {
                echo "Failed to set barcode in KitUser : " . $bad_code;
                exit();
            }
        }
    }

    public function getName()
    {
        return $this->getFirstName() . " " . $this->getLastName();
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($newName)
    {
        $this->firstName = $newName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($newName)
    {
        $this->lastName = $newName;
    }

    public function getAwardLevelYear()
    {
        return $this->getAwardLevel() . " " . $this->getExpedYear();
    }

    public function getAwardLevel()
    {
        return $this->awardLevel;
    }

    public function setAwardLevel($newLevel)
    {
        $this->awardLevel = $newLevel;
    }

    public function getExpedYear()
    {
        return $this->expedYear;
    }

    public function setExpedYear($newYear)
    {
        $this->expedYear = $newYear;
    }

    public function update()
    {
        // send current properties to the database
        $dbconn = db_connect();
        $stmnt = $dbconn->prepare(
            "INSERT INTO tUsers
            VALUES(?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                                    first_name = ?, 
                                    initial = ?, 
                                    `group` = ?"
        );
        $stmnt->bind_param("dssdssd",
            $this->barcode,
            $this->firstName,
            $this->lastName,
            $this->group,
            $this->firstName,
            $this->lastName,
            $this->group);
        $stmnt->execute();
        $stmnt->close();
        $dbconn->close();
    }
    
    
    public static function get_user_selectors($grpid) {
        <section>
            <div>
              <input type="radio" id="control_01" name="select" value="1" checked>
              <label for="control_01">
                <h2>Pfft</h2>
                <p>Awww, poor baby. Too afraid of the scary game sprites? I laugh at you.</p>
              </label>
            </div>
            <div>
              <input type="radio" id="control_02" name="select" value="2">
              <label for="control_02">
                <h2>Wannabe</h2>
                <p>You're not a gaming God by any stretch of the imagination.</p>
              </label>
            </div>
            <div>
              <input type="radio" id="control_03" name="select" value="3">
              <label for="control_03">
                <h2>Daaamn</h2>
                <p>Now we're talking. It's gettin' a bit hairy out there in game land.</p>
              </label>
            </div>
            <div>
              <input type="radio" id="control_04" name="select" value="4" disabled>
              <label for="control_04">
                <h2>Mental</h2>
                <p>Prepare for glory! This is going to be one hell of a no-holds-barred ride.</p>
              </label>
            </div>
            <div>
              <input type="radio" id="control_05" name="select" value="5">
              <label for="control_05">
                <h2>Suicidal</h2>
                <p>You will not live. Strap in and write a heart-felt letter to your loved ones.</p>
              </label>
            </div>
            </section>
                
    }
}