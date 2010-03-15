<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * 
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('CodendiUpgrade.class.php');

class Update_025 extends CodendiUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();
        $table_app = 'plugin_docman_approval';
        $table_app_user = 'plugin_docman_approval_user';
      
        $sql = ' ALTER TABLE plugin_docman_approval_user ADD INDEX idx_review (reviewer_id, table_id)';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while adding index 'idx_review' in 'plugin_docman_approval_user': ".$this->da->isError());
        }

        $sql = ' ALTER TABLE  plugin_docman_approval ADD INDEX  idx_owner (table_owner, table_id)';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while adding index 'idx_owner' in 'plugin_docman_approval': ".$this->da->isError());
        }
        echo $this->getLineSeparator();

    }
}
?>
