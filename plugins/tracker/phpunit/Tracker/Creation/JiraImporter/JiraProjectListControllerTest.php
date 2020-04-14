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
use Mockery;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Tracker\Creation\TrackerCreationPermissionChecker;

final class JiraProjectListControllerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapperBuilder
     */
    private $wrapper_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraProjectBuilder
     */
    private $project_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;
    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;
    /**
     * @var Mockery\Mock|JiraProjectListController
     */
    private $controller;

    protected function setUp(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(\Project::class);

        $project_manager = Mockery::mock(ProjectManager::instance());
        $project_manager->shouldReceive('getValidProjectByShortNameOrId')->andReturn($project)->once();

        $permission_checker = Mockery::mock(TrackerCreationPermissionChecker::class);
        $permission_checker->shouldReceive('checkANewTrackerCanBeCreated')->withArgs([$project, $user])->once();

        $this->project_builder = Mockery::mock(JiraProjectBuilder::class);
        $this->wrapper_builder = Mockery::mock(ClientWrapperBuilder::class);

        $this->controller = new JiraProjectListController(
            $project_manager,
            $permission_checker,
            $this->project_builder,
            $this->wrapper_builder
        );

        $this->request = Mockery::mock(HTTPRequest::class);
        $this->request->shouldReceive('getCurrentUser')->andReturn($user);

        $this->layout = Mockery::mock(BaseLayout::class);
    }

    public function testItReturnsAProjectList(): void
    {
        $body = new \stdClass();
        $this->request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $wrapper = Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->andReturn([]);

        $this->project_builder->shouldReceive('build')->andReturn([]);
        $this->wrapper_builder->shouldReceive('buildFromRequest')->andReturn($wrapper);

        $this->layout->shouldReceive('sendJSON')->with([]);
        $this->controller->process($this->request, $this->layout, ['project_name' => 'MyProject']);
    }
}
