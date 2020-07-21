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
 *Add anonymous column in filemodule_monitor table.
 */
class b201208241130_add_anonymous_in_filemodule_monitor extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add anonymous column in filemodule_monitor table.
EOT;
    }

    /**
     * Obtain the API
     *
     * @return Void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Add the column
     *
     * @return Void
     */
    public function up()
    {
        $sql = 'ALTER TABLE filemodule_monitor ADD COLUMN anonymous TINYINT(1) NOT NULL DEFAULT 1';
        if ($this->db->tableNameExists('filemodule_monitor')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column anonymous to table filemodule_monitor');
            }
        }
    }

    /**
     * Verify the result
     *
     * @return Void
     */
    public function postUp()
    {
        if (! $this->db->columnNameExists('filemodule_monitor', 'anonymous')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column anonymous not created in filemodule_monitor');
        }
    }
}
