<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class b201416121631_add_link_version_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Creating table plugin_docman_link_version
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_docman_link_version (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    item_id INT(11) UNSIGNED NOT NULL,
                    number INT(11) UNSIGNED NOT NULL,
                    user_id INT(11) UNSIGNED NOT NULL,
                    label TEXT NULL,
                    changelog TEXT NULL,
                    date INT(11) UNSIGNED NULL,
                    link_url TEXT NULL,
                    PRIMARY KEY(id),
                    KEY item_id (item_id)
                )";
        $this->db->createTable('plugin_docman_link_version', $sql);

        $populate = "INSERT INTO plugin_docman_link_version
                        (item_id, number, user_id, label, date, link_url)
                    SELECT item_id, 1, user_id, title, update_date, link_url
                    FROM plugin_docman_item
                    WHERE item_type = 3";
        $this->db->dbh->exec($populate);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_docman_link_version')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('plugin_docman_link_version table is missing');
        }
    }
}
