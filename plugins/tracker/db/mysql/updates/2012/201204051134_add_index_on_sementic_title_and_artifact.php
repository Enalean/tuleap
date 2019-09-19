<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201204051134_add_index_on_sementic_title_and_artifact extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Tracker cross search needs new index on tracker_artifact and tracker_semantic_title to be efficient.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE tracker_artifact ADD INDEX idx_last_changeset_id (last_changeset_id, id)";
        $msg = "adding index on `last_changeset_id` on `tracker_artifact`";
        $this->executeQuery($sql, $msg);

        $sql = "ALTER TABLE tracker_semantic_status ADD INDEX idx_ovi_fi (open_value_id, field_id)";
        $msg = "adding index on (`open_value_id`, `field_id`) on `tracker_semantic_status`";
        $this->executeQuery($sql, $msg);
    }

    private function executeQuery($sql, $msg)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            $error_detail  = implode(', ', $this->db->dbh->errorInfo());
            $error_message = "An error occured while $msg: $error_detail";
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
