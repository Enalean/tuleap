<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

class b201205231210_add_notification_occurence_column extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add notification_occurence column to plugin_docman_approval table
EOT;
    }

    /**
     * Obtain the bucket API
     *
     * @retun Void
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
        $sql = "ALTER TABLE plugin_docman_approval ADD COLUMN notification_occurence INT(11) DEFAULT 0 AFTER notification";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column notification_occurence to the table plugin_docman_approval');
        }
    }

    /**
     * Verify the column is added
     *
     * @return void
     */
    public function postUp()
    {
        if (!$this->db->columnNameExists('plugin_docman_approval', 'notification_occurence')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column notification_occurence in table plugin_docman_approval still not created');
        }
    }
}
