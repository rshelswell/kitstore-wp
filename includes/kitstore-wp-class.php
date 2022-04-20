<?php

class Kitstore-wp {

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
    }
    if (!empty($message)) {
        $infobox = <<<HTML
            <div class="alert alert-info" role="alert">
                $message
            </div>
HTML;
        echo $infobox;
    }

    function sign_out_ui() {
        
        $output = <<<HTML
        [kit_sign_out_message]
        <p class="instructions">Choose user from list and scan barcode(s) for any equipment
            borrowed.</p>
        <form action="sign_out.php" method="post">
            <div class="form-group" id="group_select">
                <label for="group-selector">Choose user's group</label>
                <select class="form-control" id="group-selector" name="group-selector"
                        onchange="show_participant_choices(1)">
                    <option value="0">All users</option>
                </select>
            </div>
            <div class="form-group" id="participant-input">

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

        <div class="modal" id="livestream_scanner">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-label="Close" class="close" data-dismiss="modal" type="button">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">Barcode Scanner</h4>
                    </div>
                    <div class="modal-body" style="position: static">
                        <div class="viewport" id="interactive"></div>
                        <div class="error"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" data-dismiss="modal" type="button">Close</button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
HTML;
    }
}
?>