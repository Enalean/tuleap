<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

class b201110171036_add_docman_approval_user_index extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add index on reviewer_id and table_id in docman_approval_user
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        //Simulate indexNameExists
        $sql = 'SHOW INDEX FROM plugin_docman_approval_user WHERE Key_name LIKE "idx_reviewer"';

        $sth = $this->db->dbh->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll();

        //If index already exists, delete it.
        if (!empty($res)) {
            $sql = 'DROP INDEX idx_reviewer ON plugin_docman_approval_user';
            $res = $this->db->dbh->exec($sql);
        }

        $sql = 'ALTER TABLE plugin_docman_approval_user' .
               ' ADD INDEX idx_reviewer (reviewer_id, table_id)';
        $this->db->addIndex('plugin_docman_approval_user', 'idx_reviewer', $sql);
    }

    public function postUp()
    {
        // As of forgeupgrade 1.2 indexNameExists is buggy, so cannot rely on it for post upgrade check
        // Assume it's ok...

        /*if (!$this->db->indexNameExists('plugin_docman_approval_user', 'idx_reviewer')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Index "idx_reviewer" is missing in "plugin_docman_approval_user"');
            }*/
    }
}
