<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

class b201910241530_remove_callmeback_configuration_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Remove call me back configuration tables';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->dropCallMeBackTables();
        $this->db->dbh->commit();
    }

    private function dropCallMeBackTables()
    {
        $sql = "DROP TABLE IF EXISTS plugin_callmeback_email;
                DROP TABLE IF EXISTS plugin_callmeback_messages;";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Remove call me back tables failed');
        }
    }
}
