<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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


class GitLog
{
    /**
     * @var CodendiDataAccess
     */
    private $data_access;

    /**
     * Constructor of the class
     *
     * @return Void
     */
    public function __construct()
    {
        $this->data_access = CodendiDataAccess::instance();
    }

    /**
     * Returns the SQL request & form field for the Git pushes
     *
     * @param Array $params Log parameters
     *
     * @return Void
     */
    public function logsDaily($params)
    {
        $params['logs'][] = [
            'sql'   => $this->getSqlStatementForLogsDaily(
                $params['group_id'],
                $params['logs_cond'],
                $this->getGitReadLogFilter($params['group_id'], $params['who'], $params['span'])
            ),
            'field' => dgettext('tuleap-git', 'Repository'),
            'title' => dgettext('tuleap-git', 'Git access'),
        ];
    }

    /**
     * Return the SQL Statement for logs daily pushs
     *
     * @param int $project_id Id of the project
     * @param String  $condition Condition
     *
     * @return String
     */
    private function getSqlStatementForLogsDaily($project_id, $condition, $full_history_condition)
    {
        $project_id = $this->data_access->escapeInt($project_id);

        return "SELECT day_last_access_timestamp AS time,
                  'read' AS type,
                  user.user_name AS user_name,
                  user.realname AS realname, user.email AS email,
                  git.repository_name AS title
                FROM plugin_git_log_read_daily AS log
                    INNER JOIN user USING (user_id)
                    INNER JOIN plugin_git AS git USING (repository_id)
                WHERE $full_history_condition
                  AND git.project_id = $project_id
                UNION
                SELECT log.push_date AS time,
                    'write' AS type,
                    user.user_name AS user_name,
                    user.realname AS realname, user.email AS email,
                    r.repository_name AS title
                FROM (SELECT *, push_date AS time from plugin_git_log) AS log, user, plugin_git AS r
                WHERE $condition
                  AND r.project_id = $project_id
                  AND log.repository_id = r.repository_id
                ORDER BY time DESC";
    }

    /**
     * @see logs_cond in source_code_access_utils.php
     *
     * @param $group_id
     * @return string
     */
    private function getGitReadLogFilter($group_id, $who, int $span)
    {
        $filters = [$this->getWhoFilter($group_id, $who), $this->getDateFilter($span)];
        return implode(' AND ', array_filter($filters));
    }

    /**
     * @return string
     */
    private function getWhoFilter($group_id, $who)
    {
        if ($who === 'allusers') {
            return '';
        }

        $project = ProjectManager::instance()->getProject($group_id);
        $users   = $this->data_access->escapeIntImplode($project->getMembersId());
        if ($who === 'members') {
            return "user.user_id IN ($users)";
        }
        return "user.user_id NOT IN ($users)";
    }

    private function getDateFilter(int $span): string
    {
        $start_date = new DateTime();
        $start_date->sub(new DateInterval('P' . $span . 'D'));

        return 'log.day >= ' . $this->data_access->quoteSmart($start_date->format('Ymd'));
    }
}
