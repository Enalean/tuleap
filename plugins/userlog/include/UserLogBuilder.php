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

namespace Tuleap\Userlog;

use UserManager;
use UserLogDao;

class UserLogBuilder
{
    /**
     * @var UserLogDao
     */
    private $log_dao;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserLogDao $log_dao, UserManager $user_manager)
    {
        $this->log_dao      = $log_dao;
        $this->user_manager = $user_manager;
    }

    public function build($start, $end, $offset, $count)
    {
        list($timelogs, $total_count) = $this->getLogsByHour($start, $end, $offset, $count);
        $logs     = $this->groupLogsByTimestamp($timelogs);

        return array($logs, $total_count);
    }

    private function groupLogsByTimestamp(array $timelogs)
    {
        $enhanced_logs = array();
        foreach ($timelogs as $hour => $logs) {
            $timestamp_day = $logs[0]['timestamp'];
            $enhanced_logs[] = array(
                'hour'      => $this->getLabelForDate($timestamp_day),
                'timelogs'  => $logs
            );
        }

        return $enhanced_logs;
    }

    private function getLabelForDate($timestamp)
    {
        return date('d M Y', $timestamp) . ' ' .
        $GLOBALS['Language']->getText(
            'plugin_userlog',
            'label_between',
            array(date('H', $timestamp), (date('H', $timestamp) + 1))
        );
    }

    private function getLogsByHour($start, $end, $offset, $count)
    {
        $timelogs = array();
        $last_uri = '';

        $logs        = $this->log_dao->search($start, $end, $offset, $count);
        $total_count = $this->log_dao->foundRows();

        foreach ($logs as $log) {
            $hour = date('H', $log['time']);
            if ($last_uri === $log['http_request_uri']) {
                $timelogs[$hour][] = $this->enhanceLogsWithShortValues($log);
            } else {
                $timelogs[$hour][] = $this->enhanceLogsWithFullValues($log);
            }
            $last_uri = $log['http_request_uri'];
        }

        return array($timelogs, $total_count);
    }

    private function enhanceLogsWithFullValues(array $log)
    {
        $user = $this->user_manager->getUserById($log['user_id']);
        return array(
            'timestamp'           => $log['time'],
            'hour'                => date('H:i:s', $log['time']),
            'group_id'            => $log['group_id'],
            'user_id'             => $user->getName(),
            'http_request_method' => $log['http_request_method'],
            'http_request_uri'    => $log['http_request_uri'],
            'http_remote_addr'    => $log['http_remote_addr'],
            'http_referrer'       => $log['http_referer']
        );
    }

    private function enhanceLogsWithShortValues(array $log)
    {
        return array(
            'timestamp'           => $log['time'],
            'hour'                => date('H:i:s', $log['time']),
            'group_id'            => '-',
            'user_id'             => '-',
            'http_request_method' => '-',
            'http_request_uri'    => '-',
            'http_remote_addr'    => '-',
            'http_referrer'       => '-'
        );
    }
}
