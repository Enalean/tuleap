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

class b201603231358_add_nature_index_for_artifact_link_changeset extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add index on nature into tracker_changeset_value_artifactlink';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER table tracker_changeset_value_artifactlink DROP INDEX idx_reverse";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding index nature');
        }

        $sql = "ALTER table tracker_changeset_value_artifactlink ADD
                INDEX idx_reverse (artifact_id, changeset_value_id, nature(10))";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding index nature');
        }
    }
}
