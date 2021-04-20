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
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Program\BuildPlanning;
use Tuleap\ProgramManagement\Program\PlanningConfiguration\Planning;
use Tuleap\ProgramManagement\ProgramTracker;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class FeatureRepresentationBuilderTest extends TestCase
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
            new UserStoryLinkedToFeatureChecker($this->parent_dao, $this->build_planning, $this->artifact_factory)
        );
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadArtifact(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $artifact = \Mockery::mock(\Artifact::class);
        $artifact->shouldReceive('userCanView')->with($user)->andReturnFalse();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact);

        self::assertNull($this->builder->buildFeatureRepresentation($user, 1, 101, 'title'));
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadField(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $artifact = \Mockery::mock(\Artifact::class);
        $artifact->shouldReceive('userCanView')->with($user)->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->shouldReceive('getFieldById')->with(101)->andReturn($field);
        $field->shouldReceive('userCanRead')->andReturnFalse();

        self::assertNull($this->builder->buildFeatureRepresentation($user, 1, 101, 'title'));
    }

    public function testItBuildsRepresentation(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('userCanView')->with($user)->andReturnTrue();
        $artifact->shouldReceive('getXRef')->andReturn('one #1');
        $artifact->shouldReceive('getUri')->andReturn('/plugins/tracker/?aid=1');
        $tracker = \Mockery::mock(\Tracker::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $tracker->shouldReceive("getColor")->andReturn(TrackerColor::fromName("lake-placid-blue"));
        $tracker->shouldReceive("getId")->andReturn(1);
        $tracker->shouldReceive("getName")->andReturn("bug");
        $tracker->shouldReceive("getProject")->andReturn(
            new Project(['group_id' => 101, 'group_name' => "My project"])
        );
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 2)->once()->andReturnNull();
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 3)->once()->andReturn(\Mockery::mock(Artifact::class));

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
        $this->parent_dao->shouldReceive('isLinkedToASprintInMirroredMilestones')->andReturnTrue();
        $this->build_planning->shouldReceive('buildRootPlanning')->andReturn(
            new Planning(
                new ProgramTracker(TrackerTestBuilder::aTracker()->withId(20)->build()),
                5,
                'Release plan',
                [50, 60],
                new \Tuleap\ProgramManagement\Project(1, 'my-porject', "My project")
            )
        );

        $expected = new FeatureRepresentation(
            1,
            'title',
            'one #1',
            '/plugins/tracker/?aid=1',
            MinimalTrackerRepresentation::build($tracker),
            $background_color,
            true,
            true
        );

        self::assertEquals($expected, $this->builder->buildFeatureRepresentation($user, 1, 101, 'title'));
    }
}
