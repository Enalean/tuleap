<?php
/**
 * Copyright (c) Enalean SAS 2016 - Present. All rights reserved
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

class b201604071310_replace_primary_key_in_table_columns extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Replace primary key in table columns';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE tracker_report_renderer_table_columns
            DROP INDEX `PRIMARY`,
            ADD INDEX column_idx(renderer_id, field_id)
        ";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while replacing primary key in table columns: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
