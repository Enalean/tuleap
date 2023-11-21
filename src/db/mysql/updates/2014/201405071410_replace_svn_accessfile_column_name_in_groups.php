<?php
/**
 * Copyright (c) Enalean SAS 2014 - Present. All rights reserved
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

/**
 * Replace svn_accessfile column name in groups by svn_accessfile_version_id
 */
class b201405071410_replace_svn_accessfile_column_name_in_groups extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return "svn_accessfile_version_id in groups";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE `groups`
                CHANGE svn_accessfile svn_accessfile_version_id INT(11)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while changing svn_accessfile column name in groups.');
        }
    }
}
