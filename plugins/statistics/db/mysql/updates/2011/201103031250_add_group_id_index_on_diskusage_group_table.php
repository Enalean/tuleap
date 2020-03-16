<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class b201103031250_add_group_id_index_on_diskusage_group_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add index on group_id and date on plugin_statistics_diskusage_group table in order to speed-up
Computation of statistics in project pages.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->log->warn('Following operations might take a while, please be patient...');
        $sql = 'ALTER TABLE plugin_statistics_diskusage_group' .
               ' ADD INDEX idx_group_id_date (group_id, date)';
        $this->db->addIndex('plugin_statistics_diskusage_group', 'idx_group_id_date', $sql);
    }

    public function postUp()
    {
        // As of forgeupgrade 1.2 indexNameExists is buggy, so cannot rely on it for post upgrade check
        // Assume it's ok...

        /*if (!$this->db->indexNameExists('plugin_statistics_diskusage_group', 'idx_group_id_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Index "idx_group_id_date" is missing in "plugin_statistics_diskusage_group"');
            }*/
    }
}
