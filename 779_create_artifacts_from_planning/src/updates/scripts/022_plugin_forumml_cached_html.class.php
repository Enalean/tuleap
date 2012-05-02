<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once 'CodendiUpgrade.class.php';

/**
 * Add column to store transformed text content
 */
class Update_022 extends CodendiUpgrade {

   function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        if ($this->tableExists('plugin_forumml_message')) {
            if (!$this->fieldExists('plugin_forumml_message', 'cached_html')) {
                $sql = "alter table plugin_forumml_message add column cached_html mediumtext default null after msg_type";
                if (!$this->update($sql)) {
                    $this->addUpgradeError("An error occured while adding column 'cached_html' in 'plugin_forumml_message': ".$this->da->isError());
                }
            }
        }
    }
}

?>