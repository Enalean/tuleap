<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\layout\HomePage;

use EventManager;
use Project;
use SVN_LogDao;
use UserManager;

class StatisticsCollectionBuilder
{
    public const CONFIG_DISPLAY_STATISTICS = 'display_homepage_statistics';

    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var SVN_LogDao
     */
    private $svn_dao;

    public function __construct(
        \ProjectManager $project_manager,
        UserManager $user_manager,
        EventManager $event_manager,
        SVN_LogDao $svn_dao
    ) {
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
        $this->event_manager   = $event_manager;
        $this->svn_dao         = $svn_dao;
    }

    public function build(): StatisticsCollection
    {
        $collection = new StatisticsCollection();

        if (\ForgeConfig::get(self::CONFIG_DISPLAY_STATISTICS)) {
            $timestamp = (new \DateTimeImmutable('-1 month'))->getTimestamp();

            $collection->addStatistic(_('Projects'), $this->countAllProjects(), $this->countProjectRegisteredLastMonth($timestamp));
            $collection->addStatistic(_('Users'), $this->countAllUsers(), $this->countUsersRegisteredLastMonth($timestamp));

            $count_SVN_commits = $this->countSVNCommits();
            if ($count_SVN_commits > 0) {
                $collection->addStatistic(_('SVN Commits'), $count_SVN_commits, $this->countSVNCommitDoneLastMonth($timestamp));
            }

            $event = new StatisticsCollectionCollector($collection, $timestamp);
            $this->event_manager->processEvent($event);
        }

        return $collection;
    }

    private function countAllUsers()
    {
        return $this->user_manager->countAllAliveUsers();
    }

    private function countUsersRegisteredLastMonth(int $timestamp)
    {
        return $this->user_manager->countAliveRegisteredUsersBefore($timestamp);
    }

    private function countAllProjects()
    {
        return $this->project_manager->countProjectsByStatus(Project::STATUS_ACTIVE);
    }

    private function countProjectRegisteredLastMonth(int $timestamp)
    {
        return $this->project_manager->countRegisteredProjectsBefore($timestamp);
    }

    private function countSVNCommits(): int
    {
        $core_commits = $this->svn_dao->countSVNCommits();

        $event = new StatisticsCollectorSVN();
        $this->event_manager->processEvent($event);

        return $core_commits + $event->getSVNPluginCommitsCount();
    }

    private function countSVNCommitDoneLastMonth(int $timestamp): int
    {
        $core_commits =  $this->svn_dao->countSVNCommitsBefore($timestamp);

        $event = new LastMonthStatisticsCollectorSVN($timestamp);
        $this->event_manager->processEvent($event);

        return $core_commits + $event->getSVNPluginCommitsCount();
    }
}
