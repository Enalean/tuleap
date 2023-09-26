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

class ListActivitiesController implements DispatchableWithRequest, DispatchableWithBurningParrot
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

        $users = ['users' => []];
        foreach ($this->activity_logger_dao->getLastWeekActiveUsers() as $user_row) {
            $connexion_count = $this->activity_logger_dao->getConnexionCount($user_row['user_id']);
            $actions_count   = $this->activity_logger_dao->getActionsCount($user_row['user_id']);
            $times           = $this->activity_logger_dao->getUsersMinMaxDates($user_row['user_id']);
            $last_seen       = new \DateTimeImmutable(sprintf('@%d', $times['max_time']));
            $first_seen      = new \DateTimeImmutable(sprintf('@%d', $times['min_time']));

            $users['users'][] = new DailyUsageRowPresenter(
                $user_row['realname'],
                $user_row['user_name'],
                $user_row['email'],
                $actions_count,
                $connexion_count,
                $last_seen,
                $last_seen->diff($first_seen),
            );
        }

        $layout->header(\Tuleap\Layout\HeaderConfiguration::fromTitle('Create Test Environment Daily Activities'));
        $this->renderer->renderToPage('daily-usage', $users);
        $layout->footer([]);
    }
}
