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
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\TrackerCreationPermissionChecker;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraClientStub;

#[DisableReturnValueGenerationForTestDoubles]
final class JiraTrackersListControllerTest extends TestCase
{
    private ClientWrapperBuilder&MockObject $wrapper_builder;
    private JiraTrackerBuilder&MockObject $project_builder;
    private BaseLayout&MockObject $layout;
    private HTTPRequest $request;
    private JiraTrackersListController $controller;

    protected function setUp(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();

        $project_manager = $this->createMock(ProjectManager::class);
        $project_manager->expects($this->once())->method('getValidProjectByShortNameOrId')->willReturn($project);

        $permission_checker = $this->createMock(TrackerCreationPermissionChecker::class);
        $permission_checker->expects($this->once())->method('checkANewTrackerCanBeCreated')->with($project, $user);

        $this->project_builder = $this->createMock(JiraTrackerBuilder::class);
        $this->wrapper_builder = $this->createMock(ClientWrapperBuilder::class);

        $this->controller = new JiraTrackersListController(
            $project_manager,
            $permission_checker,
            $this->project_builder,
            $this->wrapper_builder
        );

        $this->request = new HTTPRequest();
        $this->request->setCurrentUser($user);

        $this->layout = $this->createMock(BaseLayout::class);
    }

    public function testItReturnsATrackerList(): void
    {
        $wrapper = JiraClientStub::aJiraClient();

        $this->project_builder->method('buildFromProjectKey')->willReturn([]);
        $this->wrapper_builder->method('buildFromRequest')->willReturn($wrapper);

        $this->layout->method('sendJSON')->with([]);
        $this->controller->process(
            $this->request,
            $this->layout,
            ['project_name' => 'MyProject', 'jira_project_key' => 'JPK']
        );
    }
}
