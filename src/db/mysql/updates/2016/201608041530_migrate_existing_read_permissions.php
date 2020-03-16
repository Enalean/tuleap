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

class b201608041530_migrate_existing_read_permissions extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Migrate existings read permissions for frs';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * The mirgation create the read permissions for FRS
     *
     * Read permission depends on plateform and project visibility
     *    -> given a plateform restricted and a project public with unrestricted user
     *         Read premission is granted to AUTHENTICATED
     *    -> given a plateform who allows anonymous and a project public with a public visibility
     *         Read premission is granted to ANONYMOUS
     *
     *    -> given a public project
     *         Read premission is granted to REGISTERED
     *
     *    -> in any other configuration
     *         Read permission is granted to PROJECT_MEMBERS
     */
    public function up()
    {
        $permission_frs_reader = 'FRS_READ';

        $restricted           = $this->getQueryAndProjectsByPlateformAccessAndProjectVisibility('restricted', 'unrestricted', 5);
        $anonymous            = $this->getQueryAndProjectsByPlateformAccessAndProjectVisibility('anonymous', 'public', 1);

        $already_set_projects = array_merge($restricted['projects'], $anonymous['projects']);
        $ugroups_query        = array_merge($restricted['ugroups'], $anonymous['ugroups']);

        $already_set_sql   = "";
        if (count($already_set_projects) > 0) {
            $already_set_sql   = "AND group_id NOT IN (" . implode(',', $already_set_projects) . ")";
        }

        $sql = "SELECT group_id FROM groups
            WHERE access = 'public'
            $already_set_sql";

        foreach ($this->db->dbh->query($sql)->fetchAll() as $public) {
            $already_set_projects[] = $public['group_id'];
            $ugroups_query[]        = "('" . $public['group_id'] . "', '$permission_frs_reader', 2)";
        }

        $already_set_sql = "";
        if (count($already_set_projects) > 0) {
            $already_set_sql = " WHERE group_id NOT IN (" . implode(',', $already_set_projects) . ")";
        }

        $sql = "SELECT group_id FROM groups
                $already_set_sql";

        foreach ($this->db->dbh->query($sql)->fetchAll() as $frs_admins) {
            $ugroups_query[] = "('" . $frs_admins['group_id'] . "', '$permission_frs_reader', 3)";
        }

        $frs_sql = "INSERT INTO frs_global_permissions VALUES " . implode(',', $ugroups_query);

        return $this->db->dbh->query($frs_sql);
    }

    private function getQueryAndProjectsByPlateformAccessAndProjectVisibility($access_mode, $visibility, $ugroup_id)
    {
        $permission_frs_reader = 'FRS_READ';

        $ugroups  = array();
        $projects = array();

        $sql = "SELECT count(*) AS nb FROM forgeconfig f WHERE name = 'access_mode' AND value = '$access_mode'";
        $row = $this->db->dbh->query($sql)->fetch();
        if ($row['nb'] > 0) {
            $sql = "SELECT group_id FROM groups WHERE access = '$visibility'";
            foreach ($this->db->dbh->query($sql)->fetchAll() as $project) {
                $projects[] = $project['group_id'];
                $ugroups[]  = "('" . $project['group_id'] . "', '$permission_frs_reader', $ugroup_id)";
            }
        }

        return array(
            "ugroups"  => $ugroups,
            "projects" => $projects
        );
    }
}
