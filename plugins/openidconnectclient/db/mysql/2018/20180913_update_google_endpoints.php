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
 */

class b20180913_update_google_endpoints extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Update Google OpenID Connect provider endpoints';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $res = $this->db->dbh->exec('UPDATE plugin_openidconnectclient_provider
            SET authorization_endpoint="https://accounts.google.com/o/oauth2/v2/auth"
            WHERE authorization_endpoint="https://accounts.google.com/o/oauth2/auth"') !== false;
        $res = $res && $this->db->dbh->exec('UPDATE plugin_openidconnectclient_provider
            SET token_endpoint="https://oauth2.googleapis.com/token"
            WHERE token_endpoint="https://accounts.google.com/o/oauth2/token"') !== false;
        $res = $res && $this->db->dbh->exec('UPDATE plugin_openidconnectclient_provider
            SET user_info_endpoint="https://www.googleapis.com/oauth2/v3/userinfo"
            WHERE user_info_endpoint="https://www.googleapis.com/oauth2/v2/userinfo"') !== false;

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while updating Google OpenID Connect endpoints'
            );
        }
    }
}
