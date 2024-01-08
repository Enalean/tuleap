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
class b202401081048_add_cce_user extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add CCE User';
    }

    public function up(): void
    {
        $sql = "INSERT INTO user SET
            user_id = 70,
            user_name = 'forge__cce',
            email = 'noreply@_DOMAIN_NAME_',
            realname = 'Automated custom code execution',
            register_purpose = NULL,
            status = 'S',
            ldap_id = NULL,
            add_date = 1704707115,
            confirm_hash = NULL,
            mail_siteupdates = 0,
            mail_va = 0,
            sticky_login = 0,
            authorized_keys = NULL,
            email_new = NULL,
            timezone = 'UTC',
            language_id = 'en_US',
            last_pwd_update = '0'";
        $this->api->dbh->exec($sql);

        $sql = "INSERT INTO user_access SET
                    user_id = 70,
                    last_access_date = '0'";
        $this->api->dbh->exec($sql);
    }
}
