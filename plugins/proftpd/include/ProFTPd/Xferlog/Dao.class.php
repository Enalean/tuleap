<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\ProFTPd\Xferlog;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public const DIRECTION_UPLOAD  = 'i';
    public const DIRECTION_DOWNLOAD = 'o';
    public const DIRECTION_DELETE = 'd';

    public const SERVICE_HTTP = 'http';

    public function searchLatestEntryTimestamp()
    {
        $sql = 'SELECT * FROM plugin_proftpd_xferlog WHERE service_name != "' . self::SERVICE_HTTP . '" ORDER BY id DESC LIMIT 1';
        $dar =  $this->retrieve($sql);
        if ($dar && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['time'];
        }
        return 0;
    }

    public function storeWebDownload($user_id, $group_id, $current_time, $file_path)
    {
        $user_id      = $this->da->escapeInt($user_id);
        $group_id     = $this->da->escapeInt($group_id);
        $time         = $this->da->escapeInt($current_time);
        $file_name    = $this->da->quoteSmart($file_path);
        $direction    = $this->da->quoteSmart(self::DIRECTION_DOWNLOAD);
        $service_name = $this->da->quoteSmart(self::SERVICE_HTTP);

        $sql = "INSERT INTO plugin_proftpd_xferlog
                (
                    user_id,
                    group_id,
                    time,
                    file_name,
                    direction,
                    service_name
                )
                VALUES
                (
                    $user_id,
                    $group_id,
                    $time,
                    $file_name,
                    $direction,
                    $service_name
                )";

        return $this->update($sql);
    }

    public function store(
        $user_id,
        $group_id,
        Entry $entry
    ) {
        $user_id                = $this->da->escapeInt($user_id);
        $group_id               = $this->da->escapeInt($group_id);
        $time                   = $this->da->escapeInt($entry->current_time);
        $transfer_time          = $this->da->escapeInt($entry->transfer_time);
        $remote_host            = $this->da->quoteSmart($entry->remote_host);
        $file_size              = $this->da->escapeInt($entry->file_size);
        $file_name              = $this->da->quoteSmart($entry->filename);
        $transfer_type          = $this->da->quoteSmart($entry->transfer_type);
        $special_action_flag    = $this->da->quoteSmart($entry->special_action_flag);
        $direction              = $this->da->quoteSmart($entry->direction);
        $access_mode            = $this->da->quoteSmart($entry->access_mode);
        $username               = $this->da->quoteSmart($entry->username);
        $service_name           = $this->da->quoteSmart($entry->service_name);
        $authentication_method  = $this->da->escapeInt($entry->authentication_method);
        $authenticated_user_id  = $this->da->escapeInt($entry->authenticated_user_id);
        $completion_status      = $this->da->quoteSmart($entry->completion_status);

        $sql = "INSERT INTO plugin_proftpd_xferlog
                (
                    user_id,
                    group_id,
                    time,
                    transfer_time,
                    remote_host,
                    file_size,
                    file_name,
                    transfer_type,
                    special_action_flag,
                    direction,
                    access_mode,
                    username,
                    service_name,
                    authentication_method,
                    authenticated_user_id,
                    completion_status
                )
                VALUES
                (
                    $user_id,
                    $group_id,
                    $time,
                    $transfer_time,
                    $remote_host,
                    $file_size,
                    $file_name,
                    $transfer_type,
                    $special_action_flag,
                    $direction,
                    $access_mode,
                    $username,
                    $service_name,
                    $authentication_method,
                    $authenticated_user_id,
                    $completion_status
                )";

        return $this->update($sql);
    }

    public function getLogQuery($group_id, $where_conditions)
    {
        $group_id = $this->da->escapeInt($group_id);

        $download = $this->da->quoteSmart("Download");
        $upload   = $this->da->quoteSmart("Upload");
        $deleted  = $this->da->quoteSmart("Deleted");

        $sql = "SELECT
                    log.time AS time,
                    CASE
                        WHEN direction = '" . self::DIRECTION_DOWNLOAD . "' THEN $download
                        WHEN direction = '" . self::DIRECTION_UPLOAD . "' THEN $upload
                        WHEN direction = '" . self::DIRECTION_DELETE . "' THEN $deleted
                    END as type,
                    user.user_name AS user_name,
                    user.realname AS realname,
                    user.email AS email,
                    SUBSTR(file_name, LENGTH(groups.unix_group_name)+2, LENGTH(file_name)) AS title
                FROM plugin_proftpd_xferlog as log
                    LEFT JOIN user USING (user_id)
                    LEFT JOIN groups USING (group_id)
                WHERE group_id = $group_id
                AND $where_conditions";
        return $sql;
    }
}
