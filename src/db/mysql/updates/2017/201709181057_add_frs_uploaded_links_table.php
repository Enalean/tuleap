<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

class b201709181057_add_frs_uploaded_links_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Create frs_uploaded_links table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE frs_uploaded_links (
                  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  release_id int(11) NOT NULL,
                  release_time int(11) UNSIGNED NULL,
                  name VARCHAR(255) NOT NULL,
                  link text NOT NULL,
                  owner_id int(11) NOT NULL,
                  INDEX release_idx (release_id)
                )';

        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('frs_uploaded_links')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('project_label table is missing');
        }
    }
}
