<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class b201510131542_turn_user_groups_tables_innodb extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Turn user groups tables to innodb';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $tables = array(
            'ugroup_user',
            'user_group '
        );

        foreach ($tables as $table) {
            if (! $this->isTableInnoDB($table)) {
                $this->log->info("Convert $table");

                $sql    = "ALTER TABLE $table ENGINE = InnoDB";
                $result = $this->db->dbh->exec($sql);

                if ($result === false) {
                    $error_message = implode(', ', $this->db->dbh->errorInfo());
                    throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
                }
            }
        }
    }

    private function isTableInnoDB($table)
    {
        $sql    = "SHOW TABLE STATUS WHERE Name = '$table' AND Engine = 'InnoDB'";
        $result = $this->db->dbh->query($sql);

        return ($result->fetch() !== false);
    }
}
