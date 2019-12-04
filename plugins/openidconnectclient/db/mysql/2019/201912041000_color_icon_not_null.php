<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

final class b201912041000_color_icon_not_null extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description(): string
    {
        return 'Update OpenID Connect Client provider table to not have null icon or color';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $icon_update_res = $this->db->dbh->query("UPDATE plugin_openidconnectclient_provider SET icon = '' WHERE icon IS NULL");
        if ($icon_update_res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Cannot update null icon in plugin_openidconnectclient_provider table'
            );
        }

        $color_update_res = $this->db->dbh->query("UPDATE plugin_openidconnectclient_provider SET color = '' WHERE color IS NULL");
        if ($color_update_res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Cannot update null icon in plugin_openidconnectclient_provider table'
            );
        }

        $sql = 'ALTER TABLE plugin_openidconnectclient_provider
                MODIFY icon VARCHAR(50) NOT NULL,
                MODIFY color VARCHAR(20) NOT NULL';
        $res = $this->db->dbh->query($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Cannot add NOT NULL constraint on icon and color columns'
            );
        }
    }
}
