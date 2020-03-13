<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

class UserLogDao extends DataAccessObject
{
    public function getFoundRows()
    {
        $sql = 'SELECT FOUND_ROWS() as nb';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            return $row['nb'];
        } else {
            return -1;
        }
    }

    /**
     * @param int $start  Start date in search (timestamp).
     * @param int $end    End date in search (timestamp).
     * @param int $offset From where the result will be displayed.
     * @param int $count  How many results are returned.
     */
    public function search($start, $end, $offset, $count)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *' .
            ' FROM plugin_userlog_request' .
            ' WHERE time >= ' . $this->da->escapeInt($start) .
            ' AND time <= ' . $this->da->escapeInt($end) .
            ' ORDER BY time DESC' .
            ' LIMIT ' . $this->da->escapeInt($offset) . ', ' . $this->da->escapeInt($count);
        return $this->retrieve($sql);
    }

    public function addRequest($time, $gid, $uid, $userAgent, $requestMethod, $requestUri, $remoteAddr, $httpReferer)
    {
        $sql = 'INSERT INTO plugin_userlog_request' .
            '(time,group_id,user_id,http_user_agent,http_request_method,http_request_uri,http_remote_addr,http_referer)' .
            ' VALUES ' .
            '(' .
            $this->da->escapeInt($time) . ',' .
            $this->da->escapeInt($gid) . ',' .
            $this->da->escapeInt($uid) . ',' .
            $this->da->quoteSmart($userAgent) . ',' .
            $this->da->quoteSmart($requestMethod) . ',' .
            $this->da->quoteSmart($requestUri) . ',' .
            $this->da->quoteSmart($remoteAddr) . ',' .
            $this->da->quoteSmart($httpReferer) .
            ')';
        return $this->update($sql);
    }

    public function getLogsForDay($start, $end)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *' .
            ' FROM plugin_userlog_request' .
            ' WHERE time >= ' . $this->da->escapeInt($start) .
            ' AND time <= ' . $this->da->escapeInt($end) .
            ' ORDER BY time DESC';

        return $this->retrieve($sql);
    }
}
