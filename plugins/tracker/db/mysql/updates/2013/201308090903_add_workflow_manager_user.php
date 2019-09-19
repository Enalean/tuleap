<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class b201308090903_add_workflow_manager_user extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add Workflow User Manager';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "INSERT INTO user SET
                    user_id = 90,
                    user_name = 'forge__tracker_workflow_manager',
                    email = 'noreply@_DOMAIN_NAME_',
                    user_pw = '#~2mouahahaha',
                    realname = 'Tracker Workflow Manager',
                    register_purpose = NULL,
                    status = 'S',
                    shell = '0',
                    unix_pw = '0',
                    unix_status = '0',
                    unix_uid = 0,
                    unix_box = '0',
                    ldap_id = NULL,
                    add_date = 370514700,
                    confirm_hash = NULL,
                    mail_siteupdates = 0,
                    mail_va = 0,
                    sticky_login = 0,
                    authorized_keys = NULL,
                    email_new = NULL,
                    people_view_skills = 0,
                    people_resume = '',
                    timezone = 'GMT',
                    fontsize = 0,
                    theme = '',
                    language_id = 'en_US',
                    last_pwd_update = '0'";
        $this->executeSql($sql);

        $sql = "INSERT INTO user_access SET
                    user_id = 90,
                    last_access_date = '0'";
        $this->executeSql($sql);
    }

    private function executeSql($sql)
    {
        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
