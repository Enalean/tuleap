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

class Update_020 extends CodendiUpgrade {

    function updateParentDate($messageId, $date) {
        if ($messageId != 0) {
            $sql = 'SELECT id_parent, last_thread_update FROM plugin_forumml_message WHERE id_message = '.$messageId;
            $dar = $this->retrieve($sql);
            if ($dar && !$dar->isError()) {
                $row = $dar->current();
                if ($date > $row['last_thread_update']) {
                    $sql = 'UPDATE plugin_forumml_message'.
                        ' SET last_thread_update = '.$date.
                        ' WHERE id_message='.$messageId;
                    $this->update($sql);
                    
                    $this->updateParentDate($row['id_parent'], $date);
                }
            }
        }
    }

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        if ($this->tableExists('plugin_forumml_message')) {
            if (!$this->fieldExists('plugin_forumml_message', 'last_thread_update')) {
                $sql = 'ALTER TABLE plugin_forumml_message ADD COLUMN last_thread_update INT UNSIGNED NOT NULL DEFAULT 0 AFTER body';
                if ($this->update($sql)) {
                    $sql = 'SELECT m.id_message, id_parent, mh.value as date, last_thread_update FROM plugin_forumml_message m JOIN plugin_forumml_messageheader mh ON (mh.id_message = m.id_message) WHERE mh.id_header = 2';
                    $dar = $this->retrieve($sql);
                    foreach($dar as $row) {
                        $this->updateParentDate($row['id_message'], strtotime($row['date']));
                    }
                }
            }
        }
    }
}

?>