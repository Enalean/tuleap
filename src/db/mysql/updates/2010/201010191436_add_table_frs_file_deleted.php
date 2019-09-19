<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class b201010191436_add_table_frs_file_deleted extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add the table frs_file_deleted to manage deleted files in order to facilitate their restore later
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE frs_file_deleted (
  file_id int(11) NOT NULL,
  filename text,
  release_id int(11) NOT NULL default '0',
  type_id int(11) NOT NULL default '0',
  processor_id int(11) NOT NULL default '0',
  release_time int(11) NOT NULL default '0',
  file_size bigint NOT NULL default '0',
  post_date int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  delete_date INT(11) UNSIGNED NULL,
  purge_date INT(11) UNSIGNED NULL,
  PRIMARY KEY  (file_id),
  INDEX idx_delete_date (delete_date),
  INDEX idx_purge_date (purge_date)
);";
        $this->db->createTable('frs_file_deleted', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('frs_file_deleted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('frs_file_deleted table is missing');
        }
    }
}
