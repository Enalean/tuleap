<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class b202006011445_add_tracker_importer_user extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add Tracker Importer User';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "INSERT INTO user SET
                    user_id = 91,
                    user_name = 'forge__tracker_importer_user',
                    email = 'noreply+tracker_importer@_DOMAIN_NAME_',
                    user_pw = '#~2mouahahaha',
                    realname = 'Tracker Importer',
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
                    timezone = 'GMT',
                    language_id = 'en_US',
                    last_pwd_update = '0'";
        $this->executeSql($sql);

        $sql = "INSERT INTO user_access SET
                    user_id = 91,
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
