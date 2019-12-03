<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

class b201928110957_add_azure_openid_table extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Update and add new provider table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE plugin_openidconnectclient_provider_azure_ad (
                    provider_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
                    tenant_id TEXT NOT NULL);";

        $this->db->createTable('plugin_openidconnectclient_provider_azure_ad', $sql);
    }
}
