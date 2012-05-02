<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 *
 */
class b201108311456_add_image_renderer extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add table to store image widget
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = "CREATE TABLE IF NOT EXISTS widget_image (
                  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
                  owner_id int(11) unsigned NOT NULL default '0',
                  owner_type varchar(1) NOT NULL default 'u',
                  title varchar(255) NOT NULL,
                  url TEXT NOT NULL,
                  KEY (owner_id, owner_type)
                );";
        $this->db->createTable('widget_image', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('widget_image')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('widget_image table is missing');
        }
    }

}
?>