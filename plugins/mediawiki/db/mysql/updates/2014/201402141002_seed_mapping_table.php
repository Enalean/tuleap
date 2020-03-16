<?php
/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

class b201402141002_seed_mapping_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Seed ugroup mapping with default values
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the table
     *
     * @return void
     */
    public function up()
    {
        $sql = "INSERT INTO plugin_mediawiki_ugroup_mapping(group_id, ugroup_id, mw_group_name)
                (
                    SELECT group_id, 1, '*'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 1
                )
                UNION
                (
                    SELECT group_id, 2, 'user'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 1
                )
                UNION
                (
                    SELECT group_id, 3, 'user'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 1
                )
                UNION
                (
                    SELECT group_id, 4, 'sysop'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 1
                )
                UNION
                (
                    SELECT group_id, 4, 'bureaucrat'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 1
                )
                -- PRIVATE
                UNION
                (
                    SELECT group_id, 3, 'user'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 0
                )
                UNION
                (
                    SELECT group_id, 4, 'sysop'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 0
                )
                UNION
                (
                    SELECT group_id, 4, 'bureaucrat'
                    FROM group_plugin
                    INNER JOIN groups USING (group_id)
                    WHERE group_plugin.short_name = 'plugin_mediawiki'
                    AND status = 'A'
                    AND is_public = 0
                )";
        $this->execDB($sql, "Cannot insert initial values");
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
