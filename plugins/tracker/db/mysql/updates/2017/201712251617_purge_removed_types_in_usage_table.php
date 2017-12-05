<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201712251617_purge_removed_types_in_usage_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Purge removed types in type usage table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "DELETE plugin_tracker_projects_unused_artifactlink_types.*
                FROM plugin_tracker_projects_unused_artifactlink_types
                LEFT JOIN plugin_tracker_artifactlink_natures
                  ON (plugin_tracker_projects_unused_artifactlink_types.type_shortname = plugin_tracker_artifactlink_natures.shortname)
                WHERE LEFT(plugin_tracker_projects_unused_artifactlink_types.type_shortname, 1) != '_'
                  AND plugin_tracker_artifactlink_natures.shortname IS NULL;";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $this->rollBackOnError('An error occured while purging types');
        }
    }
}
