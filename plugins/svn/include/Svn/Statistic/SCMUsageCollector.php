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

namespace Tuleap\Svn\Statistic;

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
        $formatter->addHeader($GLOBALS['Language']->getText('plugin_svn', 'descriptor_name'));
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

        $accesses_by_month['month'][]            = $GLOBALS['Language']->getText('plugin_svn_statistics', 'month');
        $accesses_by_month['nb_browse'][]        = $GLOBALS['Language']->getText('plugin_svn_statistics', 'total_number_browse');
        $accesses_by_month['nb_read'] []         = $GLOBALS['Language']->getText('plugin_svn_statistics', 'total_number_read');
        $accesses_by_month['nb_write'] []        = $GLOBALS['Language']->getText('plugin_svn_statistics', 'total_number_write');
        $accesses_by_month['nb_project_read'] [] = $GLOBALS['Language']->getText('plugin_svn_statistics', 'total_number_project_read');
        $accesses_by_month['nb_user_read'] []    = $GLOBALS['Language']->getText('plugin_svn_statistics', 'total_number_user_read');
        $accesses_by_month['nb_project_write'][] = $GLOBALS['Language']->getText('plugin_svn_statistics', 'total_number_project_write');
        $accesses_by_month['nb_user_write'] []   = $GLOBALS['Language']->getText('plugin_svn_statistics', 'total_number_user_write');

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
                array($GLOBALS['Language']->getText('plugin_svn_statistics', 'top_user'), $top_user['user'])
            );
            $formatter->addLine(
                array(
                    $GLOBALS['Language']->getText('plugin_svn_statistics', 'top_user_operation'),
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
                array($GLOBALS['Language']->getText('plugin_svn_statistics', 'top_project'), $top_project['project'])
            );
            $formatter->addLine(
                array(
                    $GLOBALS['Language']->getText('plugin_svn_statistics', 'top_project_operation'),
                    $top_project['nb_write'])
            );
        }
    }
}
