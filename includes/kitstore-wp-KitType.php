<?php
require_once "interfaces.php";
require_once "BarcodeUtils.php";

class KitType implements dbConnection
{
    private $id = null;
    private $typeType;
    private $typeBrand;
    private $typeModel;
    private $typeDescription;
    private $typeParent;

    public function __construct($byId=null)
    {
        if ($byId == null) {
            // create a brand new KitType
            $this->typeType = null;
            $this->typeBrand = null;
            $this->typeModel = null;
            $this->typeDescription = null;
            $this->typeParent = null;
        } else {
            $mysqli = db_connect();
            $stmnt = $mysqli->prepare(
                "SELECT type, brand, model, description, parent_type
                FROM tTypes
                WHERE id = ?
                ORDER BY id ASC 
                LIMIT 1");
            $stmnt->bind_param("d", $byId);
            $stmnt->execute();
            $stmnt->bind_result($tType, $tBrand, $tModel, $tDescription, $tParent);
            $stmnt->fetch();
            $this->id = $byId;
            $this->typeType = $tType;
            $this->typeBrand = $tBrand;
            $this->typeModel = $tModel;
            $this->typeDescription = $tDescription;
            $this->typeParent = $tParent;
            $stmnt->close();
            $mysqli->close();
        }
    }

    public function set_kit_type($type_enum) {
        if (preg_match_all('/Tent|Flysheet|Inner|Poles|Pegs|Stove|Rucksack|Compass|Map/', $type_enum) === 1) {
            // it's a valid kit type
            $this->typeType = $type_enum;
            //$this->update();
        }
    }

    public function set_kit_brand($input) {
        $this->typeBrand = $input;
        //$this->update();
    }

    public function set_kit_model($input) {
        $this->typeModel = $input;
        //$this->update();
    }

    public function set_kit_description($input) {
        $this->typeDescription = $input;
        //$this->update();
    }

    public function set_kit_parent($input) {
        $this->typeParent = $input;
        //$this->update();
    }

    private function set_kit_id($input) {
        $this->id = $input;
    }

    private function update() {
        if (!(isset($this->id) && isset($this->typeType) && isset($this->typeBrand) && isset($this->typeModel) && isset($this->typeDescription) && isset($this->typeParent))) {
            $mysqli = db_connect();
            if (is_null($this->id)) {
                // insert new one and get new id
                $stmnt = $mysqli->prepare(
                    "INSERT into tTypes (type, brand, model, description, parent_type)
                VALUES (?, ? , ? , ?, ?)");
                $stmnt->bind_param("sssss", $this->typeType, $this->typeBrand, $this->typeModel, $this->typeDescription, $this->typeParent);
            } else {
                // update old one
                $stmnt = $mysqli->prepare(
                    "UPDATE tTypes
                SET type = ?, brand = ?, model = ?, description = ?, parent_type = ?
                WHERE id = ?");
                $stmnt->bind_param("sssssd", $this->typeType, $this->typeBrand, $this->typeModel, $this->typeDescription, $this->typeParent, $this->id);
            }
            $stmnt->execute();
            $tmp = $mysqli->insert_id;
            $stmnt->close();
            $mysqli->close();
        }
        $this->set_kit_id($tmp);
    }

    public function get_type() {
        return $this->typeType;
    }

    public function get_brand() {
        return $this->typeBrand;
    }

    public function get_model() {
        return $this->typeModel;
    }

    public function get_description() {
        return $this->typeDescription;
    }

    public function get_parent() {
        return $this->typeParent;
    }

    public function get_id() {
        return $this->id;
    }

    public function save() {
        $this->update();
    }

}