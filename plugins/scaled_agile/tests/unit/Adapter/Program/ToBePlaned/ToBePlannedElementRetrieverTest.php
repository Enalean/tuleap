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

namespace Tuleap\ScaledAgile\Adapter\Program\ToBePlaned;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\ScaledAgile\Program\Backlog\ToBePlanned\ToBePlannedElementsStore;
use Tuleap\ScaledAgile\Program\Plan\BuildProgram;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\REST\v1\ToBePlannedElementCollectionRepresentation;
use Tuleap\ScaledAgile\REST\v1\ToBePlannedElementRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\TrackerColor;

class ToBePlannedElementRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ToBePlannedElementsRetriever
     */
    private $retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ToBePlannedElementsStore
     */
    private $to_be_planned_element_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildProgram
     */
    private $build_program;

    protected function setUp(): void
    {
        $this->to_be_planned_element_dao = \Mockery::mock(ToBePlannedElementsStore::class);
        $this->build_program             = \Mockery::mock(BuildProgram::class);
        $this->artifact_factory          = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->form_element_factory      = \Mockery::mock(\Tracker_FormElementFactory::instance());

        $this->retriever = new ToBePlannedElementsRetriever(
            $this->build_program,
            $this->to_be_planned_element_dao,
            $this->artifact_factory,
            $this->form_element_factory
        );
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(202));
        $this->to_be_planned_element_dao->shouldReceive('searchPlannableElements')->andReturn(
            [
                ['tracker_name' => 'User stories', 'artifact_id' => '1', 'artifact_title' => 'Artifact 1', 'field_title_id' => 1],
                ['tracker_name' => 'Features', 'artifact_id' => '2', 'artifact_title' => 'Artifact 2', 'field_title_id' => 1],
            ]
        );

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->shouldReceive('getFieldById')->with(1)->andReturn($field);
        $field->shouldReceive('userCanRead')->andReturnTrue();

        $artifact_one = \Mockery::mock(\Artifact::class);
        $artifact_one->shouldReceive('userCanView')->with($user)->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact_one);
        $tracker_one = \Mockery::mock(\Tracker::class);
        $tracker_one->shouldReceive("getColor")->andReturn(TrackerColor::fromName("lake-placid-blue"));
        $tracker_one->shouldReceive("getId")->andReturn(1);
        $tracker_one->shouldReceive("getName")->andReturn("bug");
        $tracker_one->shouldReceive("getProject")->andReturn(new Project(['group_id' => 101, 'group_name' => "My project"]));
        $artifact_one->shouldReceive('getTracker')->once()->andReturn($tracker_one);

        $artifact_two = \Mockery::mock(\Artifact::class);
        $artifact_two->shouldReceive('userCanView')->with($user)->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(2)->andReturn($artifact_two);
        $tracker_two = \Mockery::mock(\Tracker::class);
        $tracker_two->shouldReceive("getColor")->andReturn(TrackerColor::fromName("deep-blue"));
        $tracker_two->shouldReceive("getId")->andReturn(2);
        $tracker_two->shouldReceive("getName")->andReturn("user stories");
        $tracker_two->shouldReceive("getProject")->andReturn(new Project(['group_id' => 101, 'group_name' => "My project"]));
        $artifact_two->shouldReceive('getTracker')->once()->andReturn($tracker_two);

        $collection = new ToBePlannedElementCollectionRepresentation(
            [
                new ToBePlannedElementRepresentation(1, 'Artifact 1', MinimalTrackerRepresentation::build($tracker_one)),
                new ToBePlannedElementRepresentation(2, 'Artifact 2', MinimalTrackerRepresentation::build($tracker_two)),
            ]
        );

        self::assertEquals($collection, $this->retriever->retrieveElements(202, $user));
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadArtifact(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(202));
        $this->to_be_planned_element_dao->shouldReceive('searchPlannableElements')->andReturn(
            [
                ['tracker_name' => 'Features', 'artifact_id' => '1', 'artifact_title' => 'Private', 'field_title_id' => 1],
            ]
        );

        $artifact = \Mockery::mock(\Artifact::class);
        $artifact->shouldReceive('userCanView')->with($user)->andReturnFalse();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact);


        $collection = new ToBePlannedElementCollectionRepresentation([]);

        self::assertEquals($collection, $this->retriever->retrieveElements(202, $user));
    }

    public function testItDoesNotReturnAnythingWhenUserCanNotReadField(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(202));
        $this->to_be_planned_element_dao->shouldReceive('searchPlannableElements')->andReturn(
            [
                ['tracker_name' => 'Features', 'artifact_id' => '1', 'artifact_title' => 'Private field', 'field_title_id' => 1],
            ]
        );

        $artifact = \Mockery::mock(\Artifact::class);
        $artifact->shouldReceive('userCanView')->with($user)->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->shouldReceive('getFieldById')->with(1)->andReturn($field);
        $field->shouldReceive('userCanRead')->andReturnFalse();

        $collection = new ToBePlannedElementCollectionRepresentation([]);

        self::assertEquals($collection, $this->retriever->retrieveElements(202, $user));
    }
}
