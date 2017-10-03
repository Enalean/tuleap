<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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



class b201709280900_mark_cleartext_api_key extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Mark all existing API keys to be able to distinguish keys that has not always been encrypted';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->addIsAPIKeyAlwaysBeenEncryptedColumn();
        $this->markCleartextKeys();
    }

    private function addIsAPIKeyAlwaysBeenEncryptedColumn()
    {
        if (! $this->db->columnNameExists('plugin_bugzilla_reference', 'has_api_key_always_been_encrypted')) {
            $sql = 'ALTER TABLE plugin_bugzilla_reference ADD COLUMN has_api_key_always_been_encrypted TINYINT(1) NOT NULL DEFAULT 1';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                    'An error occurred while adding is_api_key_always_been_encrypted column in plugin_bugzilla_reference table'
                );
            }
        }
    }

    private function markCleartextKeys()
    {
        $sql = 'UPDATE plugin_bugzilla_reference SET has_api_key_always_been_encrypted = 0 WHERE api_key != ""';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while marking cleartext Bugzilla API keys'
            );
        }
    }
}
