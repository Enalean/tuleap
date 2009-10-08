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
 * Add content-id column in plugin_forumml_attachment table
 * -> used in multipart/related messages where a part of the email relies on stuff attached in
 *    the message (for instance inline images in HTML emails).
 *
 * Add msg_type column in plugin_forumml_message table
 * -> used to detect mails send in HTML only. In this case the body stored in the DB is text/html
 *    but we have no way to detect it (while at the record time, the MIME comes with a content type)
 */
class Update_021 extends CodendiUpgrade {

   function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        if ($this->tableExists('plugin_forumml_attachment')) {
            if (!$this->fieldExists('plugin_forumml_attachment', 'content_id')) {
                $sql = "alter table plugin_forumml_attachment add column content_id varchar(255) not null default '' after file_path";
                if ($this->update($sql)) {
                    $sql = 'alter table plugin_forumml_attachment drop index idx_fk_id_message';
                    if ($this->update($sql)) {
                        $sql = "alter table plugin_forumml_attachment add index idx_fk_id_message (id_message, content_id(10));";
                        $this->update($sql);
                    }
                }
            }
        }

        if ($this->tableExists('plugin_forumml_message')) {
            if (!$this->fieldExists('plugin_forumml_message', 'msg_type')) {
                $sql = "alter table plugin_forumml_message add column msg_type varchar(30) not null default ''";
                if ($this->update($sql)) {
                    $sql  = "select id_message, body from plugin_forumml_message";
                    $dar  = $this->retrieve($sql);
                    $html = array();
                    foreach ($dar as $row) {
                        if (strpos($row['body'], '<!DOCTYPE html') === 0) {
                            $html[] = $row['id_message'];
                        }
                    }
                    if (count($html) > 0) {
                        $sql = 'update plugin_forumml_message set msg_type="text/html" where id_message IN ('.implode(',', $html).')';
                        $this->update($sql);
                    }
                }
            }
        }
    }
}

?>