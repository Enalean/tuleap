<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class b201305101622_remove_orphan_decorators extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Remove orphan decorators from the DB';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "
            DELETE td
            FROM tracker_field_list_bind_decorator AS td
            LEFT JOIN tracker_field_list_bind_static_value AS tv
            ON td.value_id = tv.id
            WHERE tv.id is null;";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
