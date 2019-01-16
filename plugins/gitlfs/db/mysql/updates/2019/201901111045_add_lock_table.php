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

class b201901111045_add_lock_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Create tables for LFS Locks';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->createTable(
            'plugin_gitlfs_lock',
            'CREATE TABLE plugin_gitlfs_lock (
              id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
              lock_path VARCHAR(255) NOT NULL,
              lock_owner INT(11) NOT NULL,
              ref VARCHAR(255),
              creation_date INT(11) UNSIGNED NOT NULL,
              repository_id INT(10) UNSIGNED NOT NULL,
              INDEX idx_lock_path (lock_path(191))
            );'
        );
    }
}
