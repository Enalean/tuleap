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

class b201510141100_add_plugin_tracker_config extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add table to store plugin tracker configuration";
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
        $this->exec(
            "CREATE TABLE plugin_tracker_config (
                name VARCHAR(255) NOT NULL,
                value VARCHAR(255) NOT NULL DEFAULT '',
                PRIMARY KEY idx(name(10))
            ) ENGINE=InnoDB",
            'An error occured while adding plugin_tracker_config table.'
        );
    }

    private function populateTable()
    {
        if ($this->isEmailgatewayActivated()) {
            $this->exec(
                "REPLACE INTO plugin_tracker_config (name, value) VALUES ('emailgateway_mode', 'token')",
                'An error occured while populating plugin_tracker_config table.'
            );
        }
    }

    private function isEmailgatewayActivated()
    {
        include($this->getLocalIncPath());
        $variables_in_localinc = get_defined_vars();

        $access_mode = 'anonymous';
        return isset($variables_in_localinc['sys_enable_reply_by_mail'])
            && $variables_in_localinc['sys_enable_reply_by_mail'] == true;
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

    private function exec($sql, $error_message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
