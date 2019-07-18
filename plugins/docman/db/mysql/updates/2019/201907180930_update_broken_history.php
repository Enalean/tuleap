<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

class b201907180930_update_broken_history extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Update broken history in docman';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $status_mapping = [
            'none'     => 100,
            'draft'    => 101,
            'approved' => 102,
            'rejected' => 103,
        ];

        foreach ($status_mapping as $string_status => $legacy_status) {
            $sql = "UPDATE plugin_docman_log SET old_value=? WHERE field='status' AND old_value=?";
            $pdo_statement = $this->db->dbh->prepare($sql);

            if (! $pdo_statement->execute([$legacy_status, $string_status])) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                    sprintf('can not update old status field for value %s', $string_status)
                );
            }

            $sql = "UPDATE plugin_docman_log SET new_value=? WHERE field='status' AND new_value=?";
            $pdo_statement = $this->db->dbh->prepare($sql);

            if (! $pdo_statement->execute([$legacy_status, $string_status])) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                    sprintf('can not update new status field for value %s', $string_status)
                );
            }
        }
    }
}
