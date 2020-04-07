<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;

require_once __DIR__ . '/../bootstrap.php';

class ConfigConformanceAsserterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ConfigConformanceValidator */
    private $validator;

    /** @var \Tracker_Artifact */
    private $artifact_outside_of_project;

    /** @var \Tracker_Artifact */
    private $execution_artifact;

    /** @var \Tracker_Artifact */
    private $another_execution_artifact;

    /** @var \Tracker_Artifact */
    private $campaign_artifact;

    /** @var \Tracker_Artifact */
    private $definition_artifact;

    private $user_id                      = 100;
    private $project_id                   = 101;
    private $campaign_tracker_id          = 444;
    private $execution_tracker_id         = 555;
    private $definition_tracker_id        = 666;
    private $another_project_id           = 102;
    private $another_execution_tracker_id = 666;

    public function setUp(): void
    {
        parent::setUp();
        $project = Mockery::spy(Project::class);
        $project->shouldReceive('getID')->andReturn($this->project_id);

        $another_project = Mockery::spy(Project::class);
        $another_project->shouldReceive('getID')->andReturn($this->another_project_id);

        $campaign_tracker = Mockery::spy(Tracker::class);
        $campaign_tracker->shouldReceive('getId')->andReturn($this->campaign_tracker_id);
        $campaign_tracker->shouldReceive('getProject')->andReturn($project);

        $definition_tracker = Mockery::spy(Tracker::class);
        $definition_tracker->shouldReceive('getId')->andReturn($this->definition_tracker_id);
        $definition_tracker->shouldReceive('getProject')->andReturn($project);

        $execution_tracker = Mockery::spy(Tracker::class);
        $execution_tracker->shouldReceive('getId')->andReturn($this->execution_tracker_id);
        $execution_tracker->shouldReceive('getProject')->andReturn($project);

        $another_execution_tracker = Mockery::spy(Tracker::class);
        $another_execution_tracker->shouldReceive('getId')->andReturn($this->another_execution_tracker_id);
        $another_execution_tracker->shouldReceive('getProject')->andReturn($another_project);

        $config = \Mockery::spy(\Tuleap\TestManagement\Config::class);
        $config->shouldReceive('getCampaignTrackerId')->with($project)->andReturn($campaign_tracker->getId());
        $config->shouldReceive('getTestDefinitionTrackerId')->with($project)->andReturn($definition_tracker->getId());
        $config->shouldReceive('getTestExecutionTrackerId')->with($project)->andReturn($execution_tracker->getId());
        $config->shouldReceive('getTestExecutionTrackerId')->with($another_project)->andReturn($another_execution_tracker->getId());

        $this->validator = new ConfigConformanceValidator($config);

        $this->user = Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturn($this->user_id);

        $this->campaign_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->definition_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->execution_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->another_execution_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->artifact_outside_of_project = \Mockery::spy(\Tracker_Artifact::class);

        $this->campaign_artifact->shouldReceive('getTracker')->andReturn($campaign_tracker);
        $this->campaign_artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturn(array($this->execution_artifact));

        $this->definition_artifact->shouldReceive('getTracker')->andReturn($definition_tracker);

        $this->execution_artifact->shouldReceive('getTracker')->andReturn($execution_tracker);
        $this->execution_artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturn(array());

        $this->another_execution_artifact->shouldReceive('getTracker')->andReturn($another_execution_tracker);
        $this->another_execution_artifact->shouldReceive('getLinkedArtifacts')->with($this->user)->andReturn(array($this->campaign_artifact));

        $tracker = Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(111);
        $tracker->shouldReceive('getProject')->andReturn($another_project);
        $this->artifact_outside_of_project->shouldReceive('getTracker')->andReturn($tracker);
    }

    public function testItReturnsFalseWhenProjectHasNoCampaignTracker()
    {
        $this->assertFalse(
            $this->validator->isArtifactACampaign($this->artifact_outside_of_project)
        );
    }

    public function testItReturnsFalseWhenTrackerIsNotACampaignTracker()
    {
        $this->assertFalse(
            $this->validator->isArtifactACampaign($this->execution_artifact)
        );
    }

    public function testItReturnsTrueWhenTrackerIsACampaignTracker()
    {
        $this->assertTrue(
            $this->validator->isArtifactACampaign($this->campaign_artifact)
        );
    }

    public function testItReturnsTrueWhenExecutionBelongsToDefinition()
    {
        $this->assertTrue(
            $this->validator->isArtifactAnExecutionOfDefinition(
                $this->execution_artifact,
                $this->definition_artifact
            )
        );
    }

    public function testItReturnsFalseWhenExecutionDoesNotBelongsToDefinition()
    {
        $this->assertFalse(
            $this->validator->isArtifactAnExecutionOfDefinition(
                $this->another_execution_artifact,
                $this->definition_artifact
            )
        );
    }
}
