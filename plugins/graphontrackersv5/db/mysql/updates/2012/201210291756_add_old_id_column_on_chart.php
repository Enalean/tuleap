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


class b201210291756_add_old_id_column_on_chart extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add new column to plugin_graphontrackersv5_chart to manage v3->v5 migration.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (!$this->db->columnNameExists('plugin_graphontrackersv5_chart', 'old_id')) {
            $sql = "ALTER TABLE plugin_graphontrackersv5_chart 
                    ADD old_id INT NULL AFTER id";
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column old_id to plugin_graphontrackersv5_chart table: ' . implode(', ', $this->db->dbh->errorInfo()));
            }
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('plugin_graphontrackersv5_chart', 'old_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('An error occured while adding column old_id to plugin_graphontrackersv5_chart');
        }
    }
}
