<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigConformanceValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ConfigConformanceValidator $validator;

    private Artifact $artifact_outside_of_project;

    private Artifact $execution_artifact;

    private Artifact $another_execution_artifact;

    private Artifact $campaign_artifact;

    private Artifact $definition_artifact;

    private $user_id                      = 100;
    private $project_id                   = 101;
    private $campaign_tracker_id          = 444;
    private $execution_tracker_id         = 555;
    private $definition_tracker_id        = 666;
    private $another_project_id           = 102;
    private $another_execution_tracker_id = 666;
    private \PFUser $user;

    public function setUp(): void
    {
        parent::setUp();
        $project = ProjectTestBuilder::aProject()->withId($this->project_id)->build();

        $another_project = ProjectTestBuilder::aProject()->withId($this->another_project_id)->build();

        $campaign_tracker = TrackerTestBuilder::aTracker()->withId($this->campaign_tracker_id)->withProject($project)->build();

        $definition_tracker = TrackerTestBuilder::aTracker()->withId($this->definition_tracker_id)->withProject($project)->build();

        $execution_tracker = TrackerTestBuilder::aTracker()->withId($this->execution_tracker_id)->withProject($project)->build();

        $another_execution_tracker = TrackerTestBuilder::aTracker()->withId($this->another_execution_tracker_id)->withProject($another_project)->build();

        $config = $this->createMock(\Tuleap\TestManagement\Config::class);
        $config->method('getCampaignTrackerId')->willReturnCallback(static fn(Project $called_project) => match ($called_project) {
            $project => $campaign_tracker->getId(),
            default => false,
        });
        $config->method('getTestDefinitionTrackerId')->with($project)->willReturn($definition_tracker->getId());
        $config->method('getTestExecutionTrackerId')->willReturnCallback(static fn(Project $called_project) => match ($called_project) {
            $project         => $execution_tracker->getId(),
            $another_project => $another_execution_tracker->getId(),
        });

        $this->validator = new ConfigConformanceValidator($config);

        $this->user = UserTestBuilder::aUser()->withId($this->user_id)->build();

        $tracker = TrackerTestBuilder::aTracker()->withId(111)->withProject($another_project)->build();

        $this->campaign_artifact           = $this->createMock(Artifact::class);
        $this->definition_artifact         = ArtifactTestBuilder::anArtifact(1)->inTracker($definition_tracker)->build();
        $this->execution_artifact          = $this->createMock(Artifact::class);
        $this->another_execution_artifact  = $this->createMock(Artifact::class);
        $this->artifact_outside_of_project = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();

        $this->campaign_artifact->method('getTracker')->willReturn($campaign_tracker);
        $this->campaign_artifact->method('getLinkedArtifacts')->with($this->user)->willReturn([$this->execution_artifact]);

        $this->execution_artifact->method('getTracker')->willReturn($execution_tracker);
        $this->execution_artifact->method('getLinkedArtifacts')->with($this->user)->willReturn([]);

        $this->another_execution_artifact->method('getTracker')->willReturn($another_execution_tracker);
        $this->another_execution_artifact->method('getLinkedArtifacts')->with($this->user)->willReturn([$this->campaign_artifact]);
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
