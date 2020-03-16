<?php
/**
 * Copyright (c) STMicroelectonics, 2017. All Rights Reserved.
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

class b201701231415_update_public_forum_in_private_projects extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Change the visibility of exisnting public forum in private projects ';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->changeForumVisibilityToPrivate();
    }

    private function changeForumVisibilityToPrivate()
    {
        $sql = "UPDATE forum_group_list f INNER JOIN groups grs
               ON f.group_id = grs.group_id
               SET is_public = 0
               WHERE is_public = 1 AND f.group_id > 100 AND grs.access='private' AND grs.status = 'A'";

        if ($this->db->dbh->exec($sql) === false) {
            $this->rollBackOnError('An error occurred while changing the visibility of the forum ' . $sql);
        }
    }

    private function rollBackOnError($message)
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
