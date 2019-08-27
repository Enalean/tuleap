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

use Cardwall_OnTop_Dao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PluginManager;
use Project;
use taskboardPlugin;
use Tuleap\Request\NotFoundException;

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
     * @var Cardwall_OnTop_Dao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PluginManager
     */
    private $plugin_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|taskboardPlugin
     */
    private $plugin;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(PFUser::class);

        $this->factory        = Mockery::mock(Planning_MilestoneFactory::class);
        $this->dao            = Mockery::mock(Cardwall_OnTop_Dao::class);
        $this->plugin_manager = Mockery::mock(PluginManager::class);
        $this->plugin         = Mockery::mock(taskboardPlugin::class);

        $this->extractor = new MilestoneExtractor($this->factory, $this->dao, $this->plugin_manager, $this->plugin);
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

    public function testNotFoundExceptionWhenProjectIsNotAllowedForPlugin(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getUnixNameMixedCase')->andReturn('my-project');
        $project->shouldReceive('getUnconvertedPublicName')->andReturn('My project');
        $project->shouldReceive('getID')->andReturn(42);

        $milestone = Mockery::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getTrackerId')->andReturn(101);
        $milestone->shouldReceive('getProject')->andReturn($project);

        $this->factory
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, 1)
            ->once()
            ->andReturn($milestone);

        $this->plugin_manager
            ->shouldReceive('isPluginAllowedForProject')
            ->with($this->plugin, 42)
            ->once()
            ->andReturnFalse();

        $this->expectException(NotFoundException::class);

        $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project']);
    }

    public function testNotFoundExceptionWhenNoCardwallOnMilestone(): void
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

        $this->plugin_manager
            ->shouldReceive('isPluginAllowedForProject')
            ->with($this->plugin, 42)
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('isEnabled')
            ->with(101)
            ->once()
            ->andReturnFalse();

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

        $this->plugin_manager
            ->shouldReceive('isPluginAllowedForProject')
            ->with($this->plugin, 42)
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('isEnabled')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $this->assertEquals(
            $milestone,
            $this->extractor->getMilestone($this->user, ['id' => 1, 'project_name' => 'my-project'])
        );
    }
}
