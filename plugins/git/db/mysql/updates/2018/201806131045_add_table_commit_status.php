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

class b201806131045_add_table_commit_status extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Create plugin_git_commit_status table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->createTable(
            'plugin_git_commit_status',
            'CREATE TABLE IF NOT EXISTS plugin_git_commit_status (
              id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
              repository_id INT(10) UNSIGNED NOT NULL,
              commit_reference CHAR(40) NOT NULL,
              status INT(1) NOT NULL,
              date INT(11) NOT NULL,
              INDEX idx_repository_commit(repository_id, commit_reference)
            );'
        );
    }
}
