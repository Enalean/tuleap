<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use HTTPRequest;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Creation\TrackerCreationPermissionChecker;

class JiraTrackersListController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var TrackerCreationPermissionChecker
     */
    private $permission_checker;
    /**
     * @var JiraTrackerBuilder
     */
    private $jira_tracker_builder;
    /**
     * @var ClientWrapperBuilder
     */
    private $wrapper_builder;

    public function __construct(
        \ProjectManager $project_manager,
        TrackerCreationPermissionChecker $permission_checker,
        JiraTrackerBuilder $jira_tracker_builder,
        ClientWrapperBuilder $wrapper_builder
    ) {
        $this->project_manager      = $project_manager;
        $this->permission_checker   = $permission_checker;
        $this->jira_tracker_builder = $jira_tracker_builder;
        $this->wrapper_builder      = $wrapper_builder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        $user    = $request->getCurrentUser();
        $this->permission_checker->checkANewTrackerCanBeCreated($project, $user);

        try {
            $wrapper  = $this->wrapper_builder->buildFromRequest($request);
            $projects = $this->jira_tracker_builder->build($wrapper, $variables['jira_project_key']);
            $layout->sendJSON($projects);
        } catch (JiraConnectionException $exception) {
            $layout->send400JSONErrors(['error' => $exception->getI18nMessage()]);
        } catch (\Exception $exception) {
            $layout->send400JSONErrors(['error' => $exception->getMessage()]);
        }
    }

    /**
     * @throws \Project_NotFoundException
     */
    public function getProject(array $variables): Project
    {
        return $this->project_manager->getValidProjectByShortNameOrId($variables['project_name']);
    }
}
