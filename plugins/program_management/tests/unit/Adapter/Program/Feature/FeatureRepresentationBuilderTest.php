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

use Project;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\BuildPlanning;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildPlanningStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveBackgroundColorStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class FeatureRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FeatureRepresentationBuilder $builder;
    private \PFUser $user;
    private UserIdentifier $user_identifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_FormElementFactory
     */
    private $form_element_factory;
    private RetrieveBackgroundColor $retrieve_background;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ArtifactsLinkedToParentDao
     */
    private $parent_dao;
    private BuildPlanning $build_planning;


    protected function setUp(): void
    {
        $this->artifact_factory     = $this->createMock(Tracker_ArtifactFactory::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->retrieve_background  = RetrieveBackgroundColorStub::withDefaults();
        $this->parent_dao           = $this->createMock(ArtifactsLinkedToParentDao::class);
        $this->build_planning       = BuildPlanningStub::withValidRootPlanning();
        $this->user                 = UserTestBuilder::aUser()->build();
        $retrieve_user              = RetrieveUserStub::withUser($this->user);
        $this->user_identifier      = UserIdentifierStub::buildGenericUser();

        $this->builder = new FeatureRepresentationBuilder(
            $this->artifact_factory,
            $this->form_element_factory,
            $this->retrieve_background,
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory, $retrieve_user),
            new UserStoryLinkedToFeatureChecker($this->parent_dao, $this->build_planning, $this->artifact_factory, $retrieve_user),
            $retrieve_user
        );
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadArtifact(): void
    {
        $program = ProgramIdentifierBuilder::buildWithId(110);

        $this->artifact_factory->method('getArtifactByIdUserCanView')->with($this->user, 1)->willReturn(null);

        self::assertNull($this->builder->buildFeatureRepresentation($this->user_identifier, $program, 1, 101, 'title'));
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadField(): void
    {
        $program = ProgramIdentifierBuilder::buildWithId(110);

        $project  = $this->buildProject(110);
        $tracker  = $this->buildTracker(14, $project);
        $artifact = $this->buildArtifact(117, $tracker);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->with($this->user, 1)->willReturn($artifact);
        $this->artifact_factory->method('getArtifactById')->with(1)->willReturn($artifact);

        $field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->method('getFieldById')->with(101)->willReturn($field);
        $field->method('userCanRead')->willReturn(false);

        self::assertNull($this->builder->buildFeatureRepresentation($this->user_identifier, $program, 1, 101, 'title'));
    }

    public function testItBuildsRepresentation(): void
    {
        $program = ProgramIdentifierBuilder::build();

        $project  = $this->buildProject(101);
        $tracker  = $this->buildTracker(1, $project);
        $artifact = $this->buildArtifact(1, $tracker);
        $this->artifact_factory->method('getArtifactById')->with(1)->willReturn($artifact);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturnMap([
            [$this->user, 1, $artifact],
            [$this->user, 2, null],
            [$this->user, 3, $this->createMock(Artifact::class)],
        ]);


        $field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->method('getFieldById')->with(101)->willReturn($field);
        $field->method('userCanRead')->willReturn(true);

        $background_color = new BackgroundColor("lake-placid-blue");

        $this->parent_dao->method('getPlannedUserStory')->willReturn(
            [
                ['user_story_id' => 1, 'project_id' => 100]
            ]
        );
        $this->parent_dao->method('getChildrenOfFeatureInTeamProjects')->willReturn(
            [
                ['children_id' => 2], ['children_id' => 3]
            ]
        );
        $this->parent_dao->method('isLinkedToASprintInMirroredProgramIncrement')->willReturn(true);

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


        self::assertEquals($expected, $this->builder->buildFeatureRepresentation($this->user_identifier, $program, 1, 101, 'title'));
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
