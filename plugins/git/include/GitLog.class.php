<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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


class GitLog {

    /**
     * @var Git_LogDao
     */
    private $_dao;

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct() {
         $this->_dao = new Git_LogDao();
    }

    /**
     * Returns the SQL request & form field for the Git pushes
     *
     * @param Array $params Log parameters
     *
     * @return Void
     */
    function logsDaily($params)
    {
        $params['logs'][] = array(
            'sql'   => $this->_dao->getSqlStatementForLogsDaily(
                $params['group_id'],
                $params['logs_cond'],
                $this->getGitReadLogFilter($params['group_id'], $params['who'], $params['span'])
            ),
            'field' => $GLOBALS['Language']->getText('plugin_git', 'logsdaily_field'),
            'title' => $GLOBALS['Language']->getText('plugin_git', 'logsdaily_title')
        );
    }

    /**
     * @see logs_cond in source_code_access_utils.php
     *
     * @param $group_id
     * @return string
     */
    private function getGitReadLogFilter($group_id, $who, $span)
    {
        return $this->getWhoFilter($group_id, $who).
            $this->getDateFilter($span);
    }

    private function getWhoFilter($group_id, $who)
    {
        $project = ProjectManager::instance()->getProject($group_id);
        if ($who == "allusers") {
            return "";
        } else {
            $users = $this->_dao->da->escapeIntImplode($project->getMembersId());
            if ($who == "members") {
                return " AND user.user_id IN ($users) ";
            }
            return " AND user.user_id NOT IN ($users) ";
        }
    }

    private function getDateFilter($span)
    {
        $start_date = new DateTime();
        $start_date->sub(new DateInterval('P'.$span.'D'));

        return 'log.day >= '.$this->_dao->da->quoteSmart($start_date->format('Ymd'));
    }
}
