<?php
/**
 * Copyright (c) Enalean 2016 - Present. All rights reserved
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

class b201610051400_use_timestamp_in_stats extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Use timestamp instead of days in git stats';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_git_full_history
                CHANGE day time int(11) unsigned NOT NULL FIRST,
                DROP git_browse_operations,
                DROP INDEX `PRIMARY`,
                ADD INDEX time_idx(time)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occurred while altering the plugin_git_full_history table.');
        }

        $sql = "UPDATE plugin_git_full_history SET time = UNIX_TIMESTAMP(time)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occurred while converting dates to timestamp.');
        }
    }
}
