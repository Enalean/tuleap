<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class b201011230835_add_column_format_to_artifact_history extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add the column format to artifact_history to distinguish text comments from html ones or other potential formats.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        // The default format is text corresponding to 0
        $sql = 'ALTER TABLE artifact_history ' .
               ' ADD format tinyint NOT NULL default 0';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column format to the table artifact_history');
        }
    }

    public function postUp()
    {
        if (!$this->db->columnNameExists('artifact_history', 'format')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column format in table artifact_history is missing');
        }
    }
}
