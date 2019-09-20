<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b20160316_add_google_default_provider extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Add Google in the default OpenID Connect providers';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "INSERT INTO plugin_openidconnectclient_provider(name, authorization_endpoint, token_endpoint, user_info_endpoint)
                VALUES (
                    'Google',
                    'https://accounts.google.com/o/oauth2/auth',
                    'https://accounts.google.com/o/oauth2/token',
                    'https://www.googleapis.com/oauth2/v2/userinfo'
                )";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding Google in the default OpenID Connect providers'
            );
        }
    }
}
