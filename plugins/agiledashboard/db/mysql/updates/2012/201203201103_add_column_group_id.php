<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
class b201203201103_add_column_group_id extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add column group_id to plugin_agiledashboard_planning table
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = "ALTER TABLE plugin_agiledashboard_planning
                ADD COLUMN group_id INT( 11 ) UNSIGNED NOT NULL AFTER name";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column group_id to plugin_agiledashboard_planning: '.implode(', ', $this->db->dbh->errorInfo()));
        }
    }
    
    public function postUp() {        
        if (!$this->db->columnNameExists('plugin_agiledashboard_planning', 'group_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('An error occured while adding column group_id to plugin_agiledashboard_planning');
        }
    }
}
?>