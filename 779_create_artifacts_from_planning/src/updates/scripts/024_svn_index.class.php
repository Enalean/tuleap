<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All rights reserved
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

/**
 * Restore right column default values for approval table.
 */
class Update_024 extends CodendiUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        $sql = 'ALTER TABLE svn_commits DROP INDEX idx_search, ADD INDEX idx_search(group_id,revision,id)';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while updating index 'idx_search' in 'svn_commits': ".$this->da->isError());
        }

        $sql = 'OPTIMIZE TABLE svn_dirs, svn_commits, svn_checkins';
        $this->update($sql);

        echo $this->getLineSeparator();

     }
}

?>
