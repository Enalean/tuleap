<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class b201604071042_create_hudson_git_job extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return 'Create table plugin_hudson_git_job for hudson_git_plugin';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_hudson_git_job (
                    id  int(11) unsigned NOT NULL auto_increment,
                    repository_id int(10) NOT NULL,
                    push_date int(11) NOT NULL,
                    job_url varchar(255) NOT NULL,
                    PRIMARY KEY  (`id`)
                )";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while creating the table plugin_hudson_git_job');
        }
    }
}
