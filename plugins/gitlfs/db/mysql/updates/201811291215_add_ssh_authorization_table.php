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
 *
 */

class b201811291215_add_ssh_authorization_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Create table to store ssh authorization tokens';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->createTable(
            'plugin_gitlfs_ssh_authorization',
            'CREATE TABLE plugin_gitlfs_ssh_authorization (
              id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
              verifier VARCHAR(255) NOT NULL,
              expiration_date INT(11) UNSIGNED NOT NULL,
              repository_id INT(10) UNSIGNED NOT NULL,
              operation_name VARCHAR(16) NOT NULL,
              user_id INT(11) NOT NULL,
              INDEX idx_expiration_date (expiration_date)
            )'
        );
    }
}
