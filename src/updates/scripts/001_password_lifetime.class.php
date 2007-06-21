<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$


require_once('CodeXUpgrade.class.php');

class Update_001 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        db_query("ALTER TABLE user ADD COLUMN last_pwd_update INT(11) UNSIGNED NOT NULL default '0'");
        if(db_error()) {
            $this->addUpgradeError(db_error());
        }
        db_query("UPDATE user SET last_pwd_update = ". time());
        if(db_error()) {
            $this->addUpgradeError(db_error());
        }
        db_query("ALTER TABLE user ADD COLUMN last_access_date INT(11) UNSIGNED NOT NULL default '0'");
        if(db_error()) {
            $this->addUpgradeError(db_error());
        }
        echo $this->getLineSeparator();
   }

}
?>
