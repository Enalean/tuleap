<?php

/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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
 *
 */
class b201207051342_add_has_avatar_column extends ForgeUpgrade_Bucket {
    public function description() {
        return <<<EOT
Add has_avatar column on user table.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'ALTER TABLE user ADD COLUMN has_avatar TINYINT(1) NOT NULL DEFAULT 0';
        if ($this->db->tableNameExists('user')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column has_avatar to table user');
            }
        }
    }

    public function postUp() {
        if (!$this->db->columnNameExists('user', 'has_avatar')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column has_avatar not created in user');
        }
    }
}

?>
