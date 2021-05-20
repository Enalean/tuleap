<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class FeatureRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildPlanning
     */
    private $build_planning;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsLinkedToParentDao
     */
    private $parent_dao;

    /**
     * @var FeatureRepresentationBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BackgroundColorRetriever
     */
    private $retrieve_background;

    protected function setUp(): void
    {
        $this->artifact_factory     = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->form_element_factory = \Mockery::mock(\Tracker_FormElementFactory::instance());
        $this->retrieve_background  = \Mockery::mock(BackgroundColorRetriever::class);
        $this->parent_dao           = \Mockery::mock(ArtifactsLinkedToParentDao::class);
        $this->build_planning       = \Mockery::mock(BuildPlanning::class);

        $this->builder = new FeatureRepresentationBuilder(
            $this->artifact_factory,
            $this->form_element_factory,
            $this->retrieve_background,
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory),
            new UserStoryLinkedToFeatureChecker($this->parent_dao, $this->build_planning, $this->artifact_factory)
        );
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadArtifact(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);

        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 1)->andReturnNull();

        self::assertNull($this->builder->buildFeatureRepresentation($user, $program, 1, 101, 'title'));
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadField(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);

        $project  = $this->buildProject(110);
        $tracker  = $this->buildTracker(14, $project);
        $artifact = $this->buildArtifact(117, $tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 1)->andReturn($artifact);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->shouldReceive('getFieldById')->with(101)->andReturn($field);
        $field->shouldReceive('userCanRead')->andReturnFalse();

        self::assertNull($this->builder->buildFeatureRepresentation($user, $program, 1, 101, 'title'));
    }

    public function testItBuildsRepresentation(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);

        $project  = $this->buildProject(101);
        $tracker  = $this->buildTracker(1, $project);
        $artifact = $this->buildArtifact(1, $tracker);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 1)->andReturn($artifact);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 2)->once()->andReturnNull();
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 3)->once()->andReturn(
            \Mockery::mock(Artifact::class)
        );

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->shouldReceive('getFieldById')->with(101)->andReturn($field);
        $field->shouldReceive('userCanRead')->andReturnTrue();

        $background_color = new BackgroundColor("lake-placid-blue");
        $this->retrieve_background->shouldReceive('retrieveBackgroundColor')->andReturn($background_color);

        $this->parent_dao->shouldReceive('getPlannedUserStory')->andReturn(
            [
                ['user_story_id' => 1, 'project_id' => 100]
            ]
        );
        $this->parent_dao->shouldReceive('getChildrenOfFeatureInTeamProjects')->andReturn(
            [
                ['children_id' => 2], ['children_id' => 3]
            ]
        );
        $this->parent_dao->shouldReceive('isLinkedToASprintInMirroredProgramIncrement')->andReturnTrue();

        $planning = new \Planning(1, "Root planning", 1, '', '', [50, 60], 20);
        $planning->setPlanningTracker(TrackerTestBuilder::aTracker()->withId(20)->build());
        $this->build_planning->shouldReceive("getRootPlanning")->andReturn($planning);

        $this->build_planning->shouldReceive("getProjectFromPlanning")->andReturn(new \Tuleap\ProgramManagement\Domain\Project(1, 'my-porject', "My project"));

        $expected = new FeatureRepresentation(
            1,
            'title',
            'bug #1',
            '/plugins/tracker/?aid=1',
            MinimalTrackerRepresentation::build($tracker),
            $background_color,
            true,
            true
        );

        self::assertEquals($expected, $this->builder->buildFeatureRepresentation($user, $program, 1, 101, 'title'));
    }

    private function buildProject(int $program_id): Project
    {
        return ProjectTestBuilder::aProject()
            ->withId($program_id)
            ->withPublicName('My project')
            ->build();
    }

    private function buildTracker(int $tracker_id, Project $program_project): \Tracker
    {
        return TrackerTestBuilder::aTracker()
            ->withId($tracker_id)
            ->withProject($program_project)
            ->withColor(TrackerColor::fromName('lake-placid-blue'))
            ->withName('bug')
            ->build();
    }

    private function buildArtifact(int $artifact_id, \Tracker $tracker): Artifact
    {
        $artifact = new Artifact($artifact_id, $tracker->getId(), 110, 1234567890, false);
        $artifact->setTracker($tracker);
        return $artifact;
    }
}
