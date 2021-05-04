<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\SourceArtifactNatureAnalyzer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ArtifactLinkFieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ChangesetValueNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ProgramNotFoundException;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SourceArtifactNatureAnalyzerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var SourceArtifactNatureAnalyzer
     */
    private $analyser;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TeamStore
     */
    private $team_store;

    protected function setUp(): void
    {
        $this->team_store       = Mockery::mock(TeamStore::class);
        $this->project_manager  = Mockery::mock(\ProjectManager::class);
        $this->artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);

        $this->analyser = new SourceArtifactNatureAnalyzer(
            $this->team_store,
            $this->project_manager,
            $this->artifact_factory
        );
    }

    public function testItThrowsExceptionWhenArtifactDoNotHaveAnyChangeset(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1);
        $user = UserTestBuilder::aUser()->build();

        $artifact->shouldReceive('getAnArtifactLinkField')->with($user)->once()->andReturnNull();

        $this->expectException(ArtifactLinkFieldNotFoundException::class);
        $this->analyser->retrieveProjectOfMirroredArtifact($artifact, $user);
    }

    public function testItThrowsExceptionWhenChangesetValueIsNotFound(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1);
        $user = UserTestBuilder::aUser()->build();

        $field = new \Tracker_FormElement_Field_ArtifactLink(
            1,
            100,
            null,
            "artlink",
            "Artifact link",
            "",
            true,
            "P",
            true,
            true,
            1
        );
        $artifact->shouldReceive('getAnArtifactLinkField')->with($user)->once()->andReturn($field);
        $artifact->shouldReceive('getValue')->once()->andReturnNull();

        $this->expectException(ChangesetValueNotFoundException::class);
        $this->analyser->retrieveProjectOfMirroredArtifact($artifact, $user);
    }

    public function testItThrowsExceptionWhenProgramIsNotFound(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1);
        $artifact->shouldReceive('getTracker')->andReturn(TrackerTestBuilder::aTracker()->withId(12)->build());
        $user = UserTestBuilder::aUser()->build();

        $field = new \Tracker_FormElement_Field_ArtifactLink(
            1,
            100,
            null,
            "artlink",
            "Artifact link",
            "",
            true,
            "P",
            true,
            true,
            1
        );
        $artifact->shouldReceive('getAnArtifactLinkField')->with($user)->once()->andReturn($field);

        $artifact_link_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $artifact->shouldReceive('getValue')->once()->andReturn($artifact_link_value);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturnNull();

        $this->expectException(ProgramNotFoundException::class);
        $this->analyser->retrieveProjectOfMirroredArtifact($artifact, $user);
    }

    public function testReturnsProjectWhenArtifactHaveMirroredMilestoneLink(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1);
        $artifact->shouldReceive('getTracker')->andReturn(TrackerTestBuilder::aTracker()->withId(12)->build());
        $user = UserTestBuilder::aUser()->build();

        $field = new \Tracker_FormElement_Field_ArtifactLink(
            1,
            100,
            null,
            "artlink",
            "Artifact link",
            "",
            true,
            "P",
            true,
            true,
            1
        );
        $artifact->shouldReceive('getAnArtifactLinkField')->with($user)->once()->andReturn($field);

        $artifact_link_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $artifact->shouldReceive('getValue')->once()->andReturn($artifact_link_value);

        $original_artifact = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($original_artifact);
        $original_artifact->shouldReceive('userCanView')->andReturnTrue();

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturn(200);
        $project = new \Project(['group_id' => 101]);
        $this->project_manager->shouldReceive('getProject')->with(200)->andReturn($project);

        $artifact_link_value->shouldReceive('getValue')->andReturn(
            [
                new Tracker_ArtifactLinkInfo(1, 'story', 101, 100, 600, '_is_child'),
                new Tracker_ArtifactLinkInfo(1, 'story', 101, 100, 600, '_mirrored_milestone')
            ]
        );

        $this->assertEquals($project, $this->analyser->retrieveProjectOfMirroredArtifact($artifact, $user));
    }

    public function testReturnsNullWhenArtifactDoesNotHaveLink(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1);
        $artifact->shouldReceive('getTracker')->andReturn(TrackerTestBuilder::aTracker()->withId(12)->build());
        $user = UserTestBuilder::aUser()->build();

        $field = new \Tracker_FormElement_Field_ArtifactLink(
            1,
            100,
            null,
            "artlink",
            "Artifact link",
            "",
            true,
            "P",
            true,
            true,
            1
        );
        $artifact->shouldReceive('getAnArtifactLinkField')->with($user)->once()->andReturn($field);

        $artifact_link_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $artifact->shouldReceive('getValue')->once()->andReturn($artifact_link_value);

        $original_artifact = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($original_artifact);
        $original_artifact->shouldReceive('userCanView')->andReturnTrue();

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->andReturn(200);
        $project = new \Project(['group_id' => 101]);
        $this->project_manager->shouldReceive('getProject')->with(200)->andReturn($project);

        $artifact_link_value->shouldReceive('getValue')->andReturn(
            [new Tracker_ArtifactLinkInfo(1, 'story', 101, 100, 600, '_is_child')]
        );

        $this->assertNull($this->analyser->retrieveProjectOfMirroredArtifact($artifact, $user));
    }

    public function testReturnsNullWhenUserCanNotSeeArtifact(): void
    {
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(1);
        $artifact->shouldReceive('getTracker')->andReturn(TrackerTestBuilder::aTracker()->withId(12)->build());
        $user = UserTestBuilder::aUser()->build();

        $field = new \Tracker_FormElement_Field_ArtifactLink(
            1,
            100,
            null,
            "artlink",
            "Artifact link",
            "",
            true,
            "P",
            true,
            true,
            1
        );
        $artifact->shouldReceive('getAnArtifactLinkField')->with($user)->once()->andReturn($field);

        $original_artifact = Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturn($original_artifact);
        $original_artifact->shouldReceive('userCanView')->andReturnFalse();

        $artifact_link_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $artifact->shouldReceive('getValue')->once()->andReturn($artifact_link_value);

        $this->team_store->shouldReceive('getProgramIncrementOfTeam')->once()->andReturn(200);
        $project = new \Project(['group_id' => 101]);
        $this->project_manager->shouldReceive('getProject')->once()->with(200)->andReturn($project);

        $artifact_link_value->shouldReceive('getValue')->once()->andReturn(
            [new Tracker_ArtifactLinkInfo(1, 'story', 101, 100, 600, '_is_child')]
        );

        $this->assertNull($this->analyser->retrieveProjectOfMirroredArtifact($artifact, $user));
    }
}
