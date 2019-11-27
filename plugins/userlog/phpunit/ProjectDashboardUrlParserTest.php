<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Userlog;

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;

final class ProjectDashboardUrlParserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectDashboardUrlParser
     */
    private $parser;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $request;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->parser = new ProjectDashboardUrlParser($this->project_manager);

        $this->request = Mockery::mock(HTTPRequest::class);
        $this->project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('102')->getMock();
    }

    public function testItReturnsNullIfURLIsNotAProjectDashboardOne(): void
    {
        $this->request->shouldReceive('getFromServer')->with('REQUEST_URI')->once()->andReturn('/a/random/url/');

        $this->project_manager->shouldReceive('getProjectByUnixName')->never();

        $this->assertNull($this->parser->getProjectIdFromProjectDashboardURL($this->request));
    }

    public function testItReturnsNullIfNoMatcheInURL(): void
    {
        $this->request->shouldReceive('getFromServer')->with('REQUEST_URI')->once()->andReturn('/projects/');

        $this->project_manager->shouldReceive('getProjectByUnixName')->never();

        $this->assertNull($this->parser->getProjectIdFromProjectDashboardURL($this->request));
    }

    public function testItReturnsNullIfProjectIsNotFound(): void
    {
        $this->request->shouldReceive('getFromServer')->with('REQUEST_URI')->once()->andReturn('/projects/toto/');

        $this->project_manager->shouldReceive('getProjectByUnixName')->once()->with('toto')->andReturnNull();

        $this->assertNull($this->parser->getProjectIdFromProjectDashboardURL($this->request));
    }

    public function testItRetrievesTheProjectIdFromSimpleProjectDashboardURL(): void
    {
        $this->request->shouldReceive('getFromServer')->with('REQUEST_URI')->once()->andReturn('/projects/toto/');

        $this->project_manager->shouldReceive('getProjectByUnixName')->once()->with('toto')->andReturn($this->project);

        $this->assertSame(
            102,
            $this->parser->getProjectIdFromProjectDashboardURL($this->request)
        );
    }

    public function testItRetrievesTheProjectIdFromSimpleProjectDashboardURLWithoutFinalSlash(): void
    {
        $this->request->shouldReceive('getFromServer')->with('REQUEST_URI')->once()->andReturn('/projects/toto');

        $this->project_manager->shouldReceive('getProjectByUnixName')->once()->with('toto')->andReturn($this->project);

        $this->assertSame(
            102,
            $this->parser->getProjectIdFromProjectDashboardURL($this->request)
        );
    }

    public function testItRetrievesTheProjectIdFromComplexeProjectDashboardURL(): void
    {
        $this->request->shouldReceive('getFromServer')->with('REQUEST_URI')->once()->andReturn('/projects/toto/?dashboard_id=2');

        $this->project_manager->shouldReceive('getProjectByUnixName')->once()->with('toto')->andReturn($this->project);

        $this->assertSame(
            102,
            $this->parser->getProjectIdFromProjectDashboardURL($this->request)
        );
    }
}
