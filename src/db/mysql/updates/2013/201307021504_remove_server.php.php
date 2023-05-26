<?php
/**
 * Copyright (c) Enalean SAS 2013 - Present. All rights reserved
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

class b201307021504_remove_server extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description()
    {
        return <<<EOT
Remove server tables
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if ($this->db->tableNameExists('server')) {
            $sql = "DROP TABLE server";
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occured while removing table server');
            }
        }
    }

    public function postUp()
    {
        if ($this->db->tableNameExists('server')) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Table server not deleted');
        }
    }
}
