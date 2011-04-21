<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201104151708_add_table_plugin_Codex_to_Remedy extends ForgeUpgrade_Bucket {

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description() {
        return <<<EOT
Add the table plugin_Codex_to_Remedy to manage the automatic ticket insertion in RIF table
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the table
     *
     * @return void
     */
    public function up() {
        $sql = 'CREATE TABLE plugin_Codex_to_Remedy ('.
                    ' id INT(11) UNSIGNED NOT NULL, '.
                    ' user_id INT(11) UNSIGNED NULL,'.
                    ' summary TEXT NOT NULL,'.
                    ' create_date INT(11) UNSIGNED NULL,'.
                    ' description TEXT NULL,'.
                    ' type INT,'.
                    ' severity INT,'.
                    ' PRIMARY KEY(id))';
        $this->db->createTable('plugin_Codex_to_Remedy', $sql);
    }

    /**
     * Verify the table creation
     *
     * @return void
     */
    public function postUp() {
        if (!$this->db->tableNameExists('plugin_Codex_to_Remedy')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_Codex_to_Remedy table is missing');
        }
    }

}

?>