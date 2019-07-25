<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/project/export/project_export_utils.php';

class UserLogExporter
{
    /**
     * @var UserLogBuilder
     */
    private $user_log_builder;

    public function __construct(UserLogBuilder $user_log_builder)
    {
        $this->user_log_builder = $user_log_builder;
    }

    public function exportLogs($day)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition:attachment; filename=users_logs.csv');
        $eol             = "\n";
        $documents_title = array(
            'date'                => $GLOBALS['Language']->getText('plugin_userlog', 'label_time'),
            'group_id'            => $GLOBALS['Language']->getText('plugin_userlog', 'label_project'),
            'user_id'             => $GLOBALS['Language']->getText('plugin_userlog', 'label_user'),
            'http_request_method' => $GLOBALS['Language']->getText('plugin_userlog', 'label_method'),
            'http_request_uri'    => $GLOBALS['Language']->getText('plugin_userlog', 'label_uri'),
            'http_remote_addr'    => $GLOBALS['Language']->getText('plugin_userlog', 'label_adress'),
            'http_referrer'       => $GLOBALS['Language']->getText('plugin_userlog', 'label_referrer')
        );

        echo build_csv_header($this->getColumnList(), $documents_title) . $eol;

        foreach ($this->user_log_builder->buildExportLogs($day) as $log_line) {
            echo build_csv_record($this->getColumnList(), $log_line) . $eol;
        }
    }

    private function getColumnList()
    {
        return array(
            'date',
            'group_id',
            'user_id',
            'http_request_method',
            'http_request_uri',
            'http_remote_addr',
            'http_referrer'
        );
    }
}
