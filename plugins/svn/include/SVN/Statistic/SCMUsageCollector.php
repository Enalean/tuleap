<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Statistic;

use Statistics_Formatter;

class SCMUsageCollector
{
    /**
     * @var SCMUsageDao
     */
    private $dao;

    public function __construct(SCMUsageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return String
     */
    public function collect(Statistics_Formatter $formatter)
    {
        $formatter->addEmptyLine();
        $formatter->addHeader(dgettext('tuleap-svn', 'SVN with multiple repositories'));
        $this->collectAccessesByMonth($formatter);
        $formatter->addEmptyLine();
        $this->collectTopUser($formatter);
        $this->collectTopProject($formatter);
        $content = $formatter->getCsvContent();
        $formatter->clearContent();

        return $content;
    }


    private function collectAccessesByMonth(Statistics_Formatter $formatter)
    {
        $accesses_by_month = array();

        $global_accesses = $this->dao->searchAccessesCount(
            $formatter->startDate,
            $formatter->endDate,
            $formatter->groupId
        );

        $accesses_by_month['month'][]            = dgettext('tuleap-svn', 'Month');
        $accesses_by_month['nb_browse'][]        = dgettext('tuleap-svn', 'Total number of SVN browse operations');
        $accesses_by_month['nb_read'][]         = dgettext('tuleap-svn', 'Total number of SVN read operations');
        $accesses_by_month['nb_write'][]        = dgettext('tuleap-svn', 'Total number of SVN write operations');
        $accesses_by_month['nb_project_read'][] = dgettext('tuleap-svn', 'Total number of projects with SVN read operations');
        $accesses_by_month['nb_user_read'][]    = dgettext('tuleap-svn', 'Total number of users with SVN read operations');
        $accesses_by_month['nb_project_write'][] = dgettext('tuleap-svn', 'Total number of projects with SVN write operations');
        $accesses_by_month['nb_user_write'][]   = dgettext('tuleap-svn', 'Total number of users with SVN write operations');

        foreach ($global_accesses as $access) {
            $month_key                                         = $access['month'] . ' ' . $access['year'];
            $accesses_by_month['month'][]                      = $access['month'] . " " . $access['year'];
            $accesses_by_month['nb_read'][]                    = $access['nb_read'];
            $accesses_by_month['nb_browse'][]                  = $access['nb_browse'];
            $accesses_by_month['nb_write'][]                   = $access['nb_write'];
            $accesses_by_month['nb_project_read'][$month_key]  = 0;
            $accesses_by_month['nb_user_read'][$month_key]     = 0;
            $accesses_by_month['nb_project_write'][$month_key] = 0;
            $accesses_by_month['nb_user_write'][$month_key]    = 0;
        }

        $project_user_read_accesses = $this->dao->searchUsersAndProjectsCountWithReadOperations(
            $formatter->startDate,
            $formatter->endDate,
            $formatter->groupId
        );
        foreach ($project_user_read_accesses as $access) {
            $month_key                                        = $access['month'] . ' ' . $access['year'];
            $accesses_by_month['nb_project_read'][$month_key] = $access['nb_project'];
            $accesses_by_month['nb_user_read'][$month_key]    = $access['nb_user'];
        }

        $project_user_write_accesses = $this->dao->searchUsersAndProjectsCountWithWriteOperations(
            $formatter->startDate,
            $formatter->endDate,
            $formatter->groupId
        );
        foreach ($project_user_write_accesses as $access) {
            $month_key                                         = $access['month'] . ' ' . $access['year'];
            $accesses_by_month['nb_project_write'][$month_key] = $access['nb_project'];
            $accesses_by_month['nb_user_write'][$month_key]    = $access['nb_user'];
        }

        foreach ($accesses_by_month as $line) {
            $formatter->addLine($line);
        }
    }

    private function collectTopUser(Statistics_Formatter $formatter)
    {
        $top_users = $this->dao->searchTopUser($formatter->startDate, $formatter->endDate, $formatter->groupId);
        foreach ($top_users as $top_user) {
            $formatter->addLine(
                array(dgettext('tuleap-svn', 'Top user'), $top_user['user'])
            );
            $formatter->addLine(
                array(
                    dgettext('tuleap-svn', 'Top user (number of write operations)'),
                    $top_user['nb_write']
                )
            );
        }
    }

    private function collectTopProject(Statistics_Formatter $formatter)
    {
        $top_projects = $this->dao->searchTopProject($formatter->startDate, $formatter->endDate, $formatter->groupId);
        foreach ($top_projects as $top_project) {
            $formatter->addLine(
                array(dgettext('tuleap-svn', 'Top project'), $top_project['project'])
            );
            $formatter->addLine(
                array(
                    dgettext('tuleap-svn', 'Top project (number of write operations)'),
                    $top_project['nb_write'])
            );
        }
    }
}
