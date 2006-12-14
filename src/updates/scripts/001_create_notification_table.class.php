<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$


require_once('CodeXUpgrade.class.php');

class Update_001 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        $sql = "SHOW TABLES LIKE 'notifications'";
        $req = db_query($sql);
        if (db_numrows($req) == 0) {
            echo "Create table notifications... ";
            $sql = "CREATE TABLE notifications (
                      user_id int( 11 ) NOT NULL ,
                      object_id int( 11 ) NOT NULL ,
                      type varchar( 100 ) NOT NULL default '',
                      PRIMARY KEY  (user_id, object_id, type)
                    );";
            $req = db_query($sql);
            if ($req) {
                echo "[DONE]";
            } else {
                echo "[ERROR]";
                $this->addUpgradeError("Unable to create table notifications. ". db_error());
            }
        } else {
            echo "Table 'notifications' already exists.";
        }
        echo $this->getLineSeparator();
    }

}
?>
