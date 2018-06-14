<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

class b201806061457_migrate_project_approval_forgeconfig extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'sys_project_approval is now stored in DB';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sys_project_approval = $this->getVariableValue();

        $sql = "REPLACE INTO forgeconfig (name, value) VALUES ('sys_project_approval', '$sys_project_approval')";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to set config value in DB.');
        }
    }

    private function getVariableValue()
    {
        include($this->getLocalIncPath());
        $variables_in_localinc = get_defined_vars();
        if (! isset($variables_in_localinc['sys_project_approval'])) {
            return 1;
        }
        return $variables_in_localinc['sys_project_approval'];
    }

    private function getLocalIncPath()
    {
        $default_path = '/etc/tuleap/conf/local.inc';
        $old_path     = '/etc/codendi/conf/local.inc';
        $local_inc    = getenv('TULEAP_LOCAL_INC') ? getenv('TULEAP_LOCAL_INC') : getenv('CODENDI_LOCAL_INC');

        if (! $local_inc) {
            if (is_file($default_path)) {
                $local_inc = $default_path;
            } else {
                $local_inc = $old_path;
            }
        }

        return $local_inc;
    }
}
