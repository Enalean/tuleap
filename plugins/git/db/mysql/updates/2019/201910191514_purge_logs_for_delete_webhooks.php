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

class b201910191514_purge_logs_for_delete_webhooks extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description(): string
    {
        return 'Purge logs for already deleted Git webhooks.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = "DELETE plugin_git_webhook_log
                FROM plugin_git_webhook_log
                    LEFT JOIN plugin_git_webhook_url ON (plugin_git_webhook_log.webhook_id = plugin_git_webhook_url.id)
                WHERE plugin_git_webhook_url.id IS NULL";
        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Error while purging logs for already removed Git webhooks .');
        }
    }
}
