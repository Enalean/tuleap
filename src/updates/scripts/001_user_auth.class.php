<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once('CodeXUpgrade.class.php');

class Update_001 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        if(!$this->fieldExists('user', 'prev_auth_success')) {
            $sql = 'ALTER TABLE user ADD COLUMN prev_auth_success INT(11) NOT NULL DEFAULT 0 AFTER last_access_date';
            $res = db_query($sql);
            if($res && !db_error()) {
                echo "Field 'prev_auth_success' added to 'user' table";
                echo $this->getLineSeparator();
            } else {
                $this->addUpgradeError("An error happened while attempting to create field 'prev_auth_success' in 'user' table: ".db_error());
            }
        }

        if(!$this->fieldExists('user', 'last_auth_success')) {
            $sql = 'ALTER TABLE user ADD COLUMN last_auth_success INT(11) NOT NULL DEFAULT 0 AFTER prev_auth_success';
            $res = db_query($sql);
            if($res && !db_error()) {
                echo "Field 'last_auth_success' added to 'user' table";
                echo $this->getLineSeparator();
            } else {
                $this->addUpgradeError("An error happened while attempting to create field 'last_auth_success' in 'user' table: ".db_error());
            }
        }

        if(!$this->fieldExists('user', 'last_auth_failure')) {
            $sql = 'ALTER TABLE user ADD COLUMN last_auth_failure INT(11) NOT NULL DEFAULT 0 AFTER last_auth_success';
            $res = db_query($sql);
            if($res && !db_error()) {
                echo "Field 'last_auth_failure' added to 'user' table";
                echo $this->getLineSeparator();
            } else {
                $this->addUpgradeError("An error happened while attempting to create field 'last_auth_failure' in 'user' table: ".db_error());
            }
        }

        if(!$this->fieldExists('user', 'nb_auth_failure')) {
            $sql = 'ALTER TABLE user ADD COLUMN nb_auth_failure INT(11) NOT NULL DEFAULT 0 AFTER last_auth_failure';
            $res = db_query($sql);
            if($res && !db_error()) {
                echo "Field 'nb_auth_failure' added to 'user' table";
                echo $this->getLineSeparator();
            } else {
                $this->addUpgradeError("An error happened while attempting to create field 'nb_auth_failure' in 'user' table: ".db_error());
            }
        }

        echo $this->getLineSeparator();
    }

}

?>
