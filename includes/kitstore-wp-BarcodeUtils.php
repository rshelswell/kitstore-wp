<?php
class BarcodeUtils {
    public $identifier;
    public $barcode;
    private static $barcodeList = array();

    private static function setBarcodeList() { 
        global $wpdb;
        $sql = $wpdb->prepare("SELECT barcode from {$wpdb->prefix}tBarcodes");
        $result = $wpdb->get_results($sql);
        foreach ($result as $bc) {
            array_push(self::$barcodeList, $bc);
        }
    }
    
    public static function barcodeInUse($testCode) {
        if (!count(self::$barcodeList)) {
            self::setBarcodeList();
        }
        if (is_a($testCode, 'BarcodeUtils')) {
            return self::barcodeInUse($testCode->getBarcode());
        }
        return in_array($testCode, self::$barcodeList);
    }

    private function getNewBarcode() {
        $rand_int = random_int(1000000, 9999999);
        $ean8 = $rand_int * 10 + self::getCheckSum($rand_int);
        if (self::barcodeInUse($ean8)) {
            return self::getNewBarcode();
        } else {
            return $ean8;
        }
    }

    public static function getUsedBarcodes() {
        if (!count(self::$barcodeList)) {
            self::setBarcodeList();
        }
        return self::$barcodeList;
    }

    public function getCheckSum($ident7=null) {
        if ($ident7 == null) {
            return $this->getCheckSum(floor($this->barcode / 10));
        }
        $check_sum = 0;
        $multiplier = 3;
        while ($ident7) {
            $check_sum += ($ident7%10)*$multiplier;
            $ident7 = floor($ident7/10);
            $multiplier = ($multiplier == 3) ? 1 : 3;
        }
        return 10 - ($check_sum % 10);
    }

    public function generateBarcode($ident7=null) {
        if ($ident7 == null){
            return $this->getBarcode();
        }
        $barcode = 10*$ident7 + $this->getCheckSum($ident7);
        return str_pad($barcode, 8, STR_PAD_LEFT);
    }

    public function saveBarcode() {
        global $wpdb;
        if ($wpdb->insert("{$wpdb->prefix}tBarcodes",
            array('barcode' => $this->barcode),
            array('%d'))) {
            // if it's now in use, add the barcode to the in use list without bothering the database again.
            self::$barcodeList[] = $this->barcode;
        } 
         
    }

    public function getBarcode() {
        return $this->barcode;
    }

    public function __construct($inData=null) {
		if ($inData == null) {
			$inData = self::getNewBarcode();
		}
        try {
            if (false == preg_match('/^\d{0,8}$/', $inData)) {
                throw new \http\Exception\InvalidArgumentException(
                    "Identifier or barcode supplied as NaN or with more than 8 digits : " . $inData
                );
            } elseif (strlen($inData) <= 7) {
                $this->barcode = $this->generateBarcode($inData);
            } elseif (strlen($inData) == 8) {
                $cs = $this->getCheckSum(floor($inData / 10));
                if ($inData % 10 == $cs) {
                    $this->barcode = $inData;
                } else {
                    $this->barcode = $inData + $cs - $inData % 10;
                }
            }
        } catch (\http\Exception\InvalidArgumentException $err) {
            echo "Barcode could not be built : " . $err;
        }
        if (count(self::$barcodeList) == 0) {
            self::setBarcodeList();
        }
    }
}
