<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2008. All rights reserved
//
// 


require_once('CodeXUpgrade.class.php');

class Update_001 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        db_query("DELETE FROM layouts_contents WHERE name = 'myserverupdate'");
        if(db_error()) {
            $this->addUpgradeError(db_error());
        }
        echo $this->getLineSeparator();
    }

}
?>
