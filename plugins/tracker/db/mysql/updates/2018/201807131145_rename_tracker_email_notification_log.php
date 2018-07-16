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

class b201807131145_rename_tracker_email_notification_log extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Rename tracker_email_notification_log table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if ($this->db->tableNameExists('tracker_post_creation_event_log')) {
            return;
        }

        $res = $this->db->dbh->query('ALTER TABLE tracker_email_notification_log RENAME tracker_post_creation_event_log');

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Renaming of the table tracker_email_notification_log failed');
        }
    }
}
