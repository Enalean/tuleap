<?php
/**
 * Copyright (c) Enalean SAS - 2013. All rights reserved
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

class b201306061508_add_interwiki_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add interwiki table
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the table
     *
     * @return void
     */
    public function up()
    {
        $sql = 'CREATE TABLE plugin_mediawiki_interwiki (
  iw_prefix varchar(32) NOT NULL,
  iw_url blob NOT NULL,
  iw_api blob NOT NULL,
  iw_wikiid varchar(64) NOT NULL,
  iw_local bool NOT NULL,
  iw_trans tinyint NOT NULL default 0
)';
        $this->execDB($sql, 'An error occured while adding plugin_mediawiki_interwiki table: ');

        $sql = 'CREATE UNIQUE INDEX iw_prefix ON plugin_mediawiki_interwiki (iw_prefix)';
        $this->execDB($sql, 'An error occured while adding index on plugin_mediawiki_interwiki: ');
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
