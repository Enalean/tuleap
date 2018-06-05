<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class b201806051554_restore_missing_art_artifact_references  extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add missing art and artifact references in reference_group_table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->restoreArtReference();
        $this->restoreArtifactReference();
        $this->db->dbh->commit();
    }

    private function restoreArtReference()
    {
        $sql = "INSERT INTO reference_group (reference_id, group_id, is_active)
                SELECT DISTINCT 1, groups.group_id, 1
                FROM groups
                  INNER JOIN service USING (group_id)
                WHERE service.short_name = 'plugin_tracker'
                  AND groups.group_id > 100
                  AND groups.group_id NOT IN (
                SELECT DISTINCT groups.group_id
                FROM groups
                  INNER JOIN reference_group USING (group_id)
                WHERE reference_id = 1)";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $this->rollBackOnError('insert of art reference failed');
        }
    }

    private function restoreArtifactReference()
    {
        $sql = "INSERT INTO reference_group (reference_id, group_id, is_active)
                SELECT DISTINCT 2, groups.group_id, 1
                FROM groups
                  INNER JOIN service USING (group_id)
                WHERE service.short_name = 'plugin_tracker'
                  AND groups.group_id > 100
                  AND groups.group_id NOT IN (
                SELECT DISTINCT groups.group_id
                FROM groups
                  INNER JOIN reference_group USING (group_id)
                WHERE reference_id = 2)";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            $this->rollBackOnError('insert of artifact reference failed');
        }
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
