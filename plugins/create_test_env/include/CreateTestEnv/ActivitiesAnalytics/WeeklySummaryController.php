<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\CreateTestEnv\ActivitiesAnalytics;

use HTTPRequest;
use Tuleap\CreateTestEnv\ActivityLogger\ActivityLoggerDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use User_ForgeUserGroupPermissionsManager;

class WeeklySummaryController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var ActivityLoggerDao
     */
    private $activity_logger_dao;
    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_user_group_permissions_manager;

    public function __construct(\TemplateRendererFactory $renderer_factory, ActivityLoggerDao $activity_logger_dao, User_ForgeUserGroupPermissionsManager $forge_user_group_permissions_manager)
    {
        $this->renderer                             = $renderer_factory->getRenderer(__DIR__ . '/templates');
        $this->activity_logger_dao                  = $activity_logger_dao;
        $this->forge_user_group_permissions_manager = $forge_user_group_permissions_manager;
    }

    /**
     * @inheritDoc
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser() && ! $this->forge_user_group_permissions_manager->doesUserHavePermission($request->getCurrentUser(), new DisplayUserActivities())) {
            throw new ForbiddenException();
        }

        $weeks = ['weeks' => []];
        foreach ($this->getWeeks() as $week) {
            $first_day_of_week = new \DateTimeImmutable($week);
            $last_day_of_week  = $first_day_of_week->add(new \DateInterval('P7D'));
            $weeks['weeks'][]  = $this->getNbActionsPerUser($week, $first_day_of_week, $last_day_of_week);
        }

        $layout->header(\Tuleap\Layout\HeaderConfiguration::fromTitle('Create Test Environment - Weekly Summary'));
        $this->renderer->renderToPage('weekly', $weeks);
        $layout->footer([]);
    }

    private function getWeeks(): array
    {
        $time_boundaries = $this->activity_logger_dao->getMinMaxTimeFromLogs();
        $first_date      = new \DateTime(sprintf('@%d', $time_boundaries['min_time']));
        $last_date       = new \DateTimeImmutable(sprintf('@%d', $time_boundaries['max_time']));

        $weeks = [];
        do {
            $weeks[$first_date->format('Y-\WW')] = 0;
            $first_date->add(new \DateInterval('P1D'));
        } while ($last_date->diff($first_date)->days !== 0);

        return array_reverse(array_keys($weeks));
    }

    private function getNbActionsPerUser(string $week, \DateTimeImmutable $first_day_of_week, \DateTimeImmutable $last_day_of_week)
    {
        $quartile = [
            10  => 0,
            50  => 0,
            100 => 0,
            101 => 0,
        ];

        $nb_actions = $this->activity_logger_dao->getActionCountBetweenDates($first_day_of_week, $last_day_of_week);

        foreach ($this->activity_logger_dao->getActionCountPerUsersBetweenDates($first_day_of_week, $last_day_of_week) as $row) {
            if ($row['nb'] <= 10) {
                $quartile[10]++;
            } elseif ($row['nb'] > 10 && $row['nb'] <= 50) {
                $quartile[50]++;
            } elseif ($row['nb'] > 50 && $row['nb'] <= 100) {
                $quartile[100]++;
            } else {
                $quartile[101]++;
            }
        }

        return new WeeklyActionsRowPresenter($week, $nb_actions, $quartile);
    }
}
