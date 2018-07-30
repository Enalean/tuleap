<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201608091537_add_system_references extends ForgeUpgrade_Bucket // phpcs:ignore
{

    public function description()
    {
        return <<<EOT
Add system references for pull request plugin.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "INSERT INTO reference (id, keyword, description, link, scope, service_short_name, nature)
                VALUES (31, 'pr', 'plugin_pullrequest:reference_pullrequest_desc_key', '/plugins/git/?action=pull-requests&repo_id=\$repo_id&group_id=\$group_id#/pull-requests/$1/overview', 'S', 'plugin_pullrequest', 'pullrequest'),
                (32, 'pullrequest', 'plugin_pullrequest:reference_pullrequest_desc_key', '/plugins/git/?action=pull-requests&repo_id=\$repo_id&group_id=\$group_id#/pull-requests/$1/overview', 'S', 'plugin_pullrequest', 'pullrequest')";

        $this->executeSql($sql);

        $sql = "INSERT INTO reference_group (reference_id, group_id, is_active)
                SELECT 31, group_id, 1 FROM groups WHERE group_id";

        $this->executeSql($sql);

        $sql = "INSERT INTO reference_group (reference_id, group_id, is_active)
                SELECT 32, group_id, 1 FROM groups WHERE group_id";

        $this->executeSql($sql);
    }

    public function executeSql($sql)
    {
        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
