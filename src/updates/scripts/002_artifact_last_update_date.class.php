<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//


require_once('CodeXUpgrade.class.php');

class Update_002 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        db_query("ALTER TABLE artifact ADD COLUMN last_update_date INT(11) UNSIGNED NOT NULL default '0' AFTER close_date");
        if(db_error()) {
            $this->addUpgradeError(db_error());
        }
        echo $this->getLineSeparator();
   }

}
?>
