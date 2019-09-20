<?php
/**
 * Copyright (c) Enalean SAS 2015. All rights reserved
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

class b201503271456_add_forgeconfig extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store forge configuration";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();
        $this->populateTable();
    }

    private function createTable()
    {
        $sql = "CREATE TABLE forgeconfig (
            name VARCHAR(255) NOT NULL,
            value VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY idx(name(10))
        )";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding forgeconfig table.');
        }
    }

    private function populateTable()
    {
        $access_mode = $this->getCurrentAccessMode();
        $sql = "REPLACE INTO forgeconfig (name, value) VALUES ('access_mode', '$access_mode')";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while populating forgeconfig table.');
        }
    }

    private function getCurrentAccessMode()
    {
        include($this->getLocalIncPath());
        $variables_in_localinc = get_defined_vars();

        $access_mode = 'anonymous';
        if (isset($variables_in_localinc['sys_allow_anon']) && $variables_in_localinc['sys_allow_anon'] == false) {
            $access_mode = 'regular';

            if (isset($variables_in_localinc['sys_allow_restricted_users']) && $variables_in_localinc['sys_allow_restricted_users'] == true) {
                $access_mode = 'restricted';
            }
        } elseif (isset($variables_in_localinc['sys_allow_restricted_users']) && $variables_in_localinc['sys_allow_restricted_users'] == true) {
            $this->log->warn('Ambiguous configuration: both sys_allow_anon & sys_allow_restricted_users were activated whereas it makes no sense. Deactivating restricted users; you can change it through the web interface.');
        }

        return $access_mode;
    }

    private function getLocalIncPath()
    {
        $default_path = '/etc/tuleap/conf/local.inc';
        $centos5_path = '/etc/codendi/conf/local.inc';
        $local_inc    = getenv('TULEAP_LOCAL_INC') ? getenv('TULEAP_LOCAL_INC') : getenv('CODENDI_LOCAL_INC');

        if (! $local_inc) {
            if (is_file($default_path)) {
                $local_inc = $default_path;
            } else {
                $local_inc = $centos5_path;
            }
        }

        return $local_inc;
    }
}
