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

/**
 *
 */
class b201305061356_add_cloudstorage_column extends ForgeUpgrade_Bucket {

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description() {
        return <<<EOT
Alter plugin_docman_item add cloudstorage column
EOT;
    }

    /**
     * Obtain the bucket API
     *
     * @retun Void
     */
    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Add the column
     *
     * @return Void
     */
    public function up() {
        $sql = "ALTER TABLE plugin_docman_item ADD COLUMN cs_docid VARCHAR(255) DEFAULT NULL AFTER link_url";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column cs_docid to the table plugin_docman_item');
        }
        
        $sql = "ALTER TABLE plugin_docman_item_deleted ADD COLUMN cs_docid VARCHAR(255) DEFAULT NULL AFTER link_url";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column cs_docid to the table plugin_docman_item_deleted');
        }   
        
        $sql = "ALTER TABLE plugin_docman_item ADD COLUMN cs_service VARCHAR(255) DEFAULT NULL AFTER cs_docid";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column cs_service to the table plugin_docman_item');
        }
        
        $sql = "ALTER TABLE plugin_docman_item_deleted ADD COLUMN cs_service VARCHAR(255) DEFAULT NULL AFTER cs_docid";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column cs_service to the table plugin_docman_item_deleted');
        }               
    }

    /**
     * Verify the column is added
     *
     * @return void
     */
    public function postUp() {
        if (!$this->db->columnNameExists('plugin_docman_item', 'cs_docid')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column cs_docid in table plugin_docman_item still not created');
        }
        if (!$this->db->columnNameExists('plugin_docman_item_deleted', 'cs_docid')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column cs_docid in table plugin_docman_item_deleted still not created');
        }   
        if (!$this->db->columnNameExists('plugin_docman_item', 'cs_service')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column cs_service in table plugin_docman_item still not created');
        }
        if (!$this->db->columnNameExists('plugin_docman_item_deleted', 'cs_service')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column cs_service in table plugin_docman_item_deleted still not created');
        }
    }
}
?>
