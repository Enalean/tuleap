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

namespace Tuleap\Taskboard\Routing;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Project;
use Tuleap\Request\NotFoundException;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsAllowedChecker;
use Tuleap\Taskboard\AgileDashboard\MilestoneIsNotAllowedException;

class MilestoneExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $factory;
    /**
     * @var MilestoneExtractor
     */
    private $extractor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MilestoneIsAllowedChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(PFUser::class);

        $this->factory        = Mockery::mock(Planning_MilestoneFactory::class);
        $this->checker = Mockery::mock(MilestoneIsAllowedChecker::class);

        $this->extractor = new MilestoneExtractor($this->factory, $this->checker);
    }

    public function testNotFoundExceptionWhenMilestoneDoesNotExist(): void
    {
        $this->factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->once()
            ->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project']);
    }

    public function testNotFoundExceptionWhenProjectMilestoneIsNotTheOneGivenInArgument(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturn('another-project');

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getTrackerId')->andReturn(101);
        $milestone->shouldReceive('getProject')->andReturn($project);

        $this->factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->once()
            ->andReturn($milestone);

        $this->expectException(NotFoundException::class);

        $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project']);
    }

    public function testNotFoundExceptionWhenMilestoneIsNotAllowed(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturn('my-project');
        $project->shouldReceive('getPublicName')->andReturn('My project');
        $project->shouldReceive('getID')->andReturn(42);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getTrackerId')->andReturn(101);
        $milestone->shouldReceive('getProject')->andReturn($project);

        $this->factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->once()
            ->andReturn($milestone);

        $this->checker
            ->shouldReceive('checkMilestoneIsAllowed')
            ->with($milestone)
            ->once()
            ->andThrow(MilestoneIsNotAllowedException::class);

        $this->expectException(NotFoundException::class);

        $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project']);
    }

    public function testItReturnsTheMilestone(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturn('my-project');
        $project->shouldReceive('getID')->andReturn(42);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getTrackerId')->andReturn(101);
        $milestone->shouldReceive('getProject')->andReturn($project);

        $this->factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->once()
            ->andReturn($milestone);

        $this->checker
            ->shouldReceive('checkMilestoneIsAllowed')
            ->with($milestone)
            ->once();

        $this->assertEquals(
            $milestone,
            $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project'])
        );
    }
}
