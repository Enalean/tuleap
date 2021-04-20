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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Program\Backlog\Feature\BackgroundColor;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\PlannedProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Program\BuildPlanning;
use Tuleap\ProgramManagement\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\TrackerColor;

final class ProgramIncrementContentRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsLinkedToParentDao
     */
    private $parent_dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BackgroundColorRetriever
     */
    private $retrieve_background;

    /**
     * @var RetrieveProgramIncrement
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
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ContentStore
     */
    private $content_store;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildProgram
     */
    private $retrieve_program_increment;

    protected function setUp(): void
    {
        $this->content_store              = \Mockery::mock(ContentStore::class);
        $this->retrieve_program_increment = \Mockery::mock(RetrieveProgramIncrement::class);
        $this->artifact_factory           = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->form_element_factory       = \Mockery::mock(\Tracker_FormElementFactory::instance());
        $this->retrieve_background        = \Mockery::mock(BackgroundColorRetriever::class);
        $this->parent_dao                 = \Mockery::mock(ArtifactsLinkedToParentDao::class);

        $this->retriever = new FeatureContentRetriever(
            $this->retrieve_program_increment,
            $this->content_store,
            new FeatureRepresentationBuilder(
                $this->artifact_factory,
                $this->form_element_factory,
                $this->retrieve_background,
                new UserStoryLinkedToFeatureChecker($this->parent_dao, \Mockery::mock(BuildPlanning::class), $this->artifact_factory)
            )
        );
    }

    public function testItBuildsACollectionOfOpenedElements(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->retrieve_program_increment->shouldReceive('retrieveProgramIncrement')->andReturn(
            new PlannedProgramIncrement(202)
        );
        $this->content_store->shouldReceive('searchContent')->andReturn(
            [
                [
                    'tracker_name'   => 'User stories',
                    'artifact_id'    => 1,
                    'artifact_title' => 'Artifact 1',
                    'field_title_id' => 1
                ],
                [
                    'tracker_name'   => 'Features',
                    'artifact_id'    => 2,
                    'artifact_title' => 'Artifact 2',
                    'field_title_id' => 1
                ],
            ]
        );

        $field = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $this->form_element_factory->shouldReceive('getFieldById')->with(1)->andReturn($field);
        $field->shouldReceive('userCanRead')->andReturnTrue();

        $artifact_one = \Mockery::mock(Artifact::class);
        $artifact_one->shouldReceive('userCanView')->with($user)->andReturnTrue();
        $artifact_one->shouldReceive('getXRef')->andReturn('one #1');
        $artifact_one->shouldReceive('getUri')->andReturn('/plugins/tracker/?aid=1');
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->andReturn($artifact_one);
        $tracker_one = \Mockery::mock(\Tracker::class);
        $tracker_one->shouldReceive("getColor")->andReturn(TrackerColor::fromName("lake-placid-blue"));
        $tracker_one->shouldReceive("getId")->andReturn(1);
        $tracker_one->shouldReceive("getName")->andReturn("bug");
        $tracker_one->shouldReceive("getProject")->andReturn(
            new Project(['group_id' => 101, 'group_name' => "My project"])
        );
        $artifact_one->shouldReceive('getTracker')->once()->andReturn($tracker_one);

        $artifact_two = \Mockery::mock(Artifact::class);
        $artifact_two->shouldReceive('userCanView')->with($user)->andReturnTrue();
        $artifact_two->shouldReceive('getXRef')->andReturn('two #2');
        $artifact_two->shouldReceive('getUri')->andReturn('/plugins/tracker/?aid=2');
        $this->artifact_factory->shouldReceive('getArtifactById')->with(2)->andReturn($artifact_two);
        $tracker_two = \Mockery::mock(\Tracker::class);
        $tracker_two->shouldReceive("getColor")->andReturn(TrackerColor::fromName("deep-blue"));
        $tracker_two->shouldReceive("getId")->andReturn(2);
        $tracker_two->shouldReceive("getName")->andReturn("user stories");
        $tracker_two->shouldReceive("getProject")->andReturn(
            new Project(['group_id' => 101, 'group_name' => "My project"])
        );
        $artifact_two->shouldReceive('getTracker')->once()->andReturn($tracker_two);

        $this->retrieve_background->shouldReceive('retrieveBackgroundColor')
            ->andReturn(new BackgroundColor("lake-placid-blue"));

        $this->parent_dao->shouldReceive('getPlannedUserStory')->andReturn([]);
        $this->parent_dao->shouldReceive('getChildrenOfFeatureInTeamProjects')->twice()->andReturn([]);

        $collection = [
            new FeatureRepresentation(
                1,
                'Artifact 1',
                'one #1',
                '/plugins/tracker/?aid=1',
                MinimalTrackerRepresentation::build($tracker_one),
                new BackgroundColor("lake-placid-blue"),
                false,
                false
            ),
            new FeatureRepresentation(
                2,
                'Artifact 2',
                'two #2',
                '/plugins/tracker/?aid=2',
                MinimalTrackerRepresentation::build($tracker_two),
                new BackgroundColor("lake-placid-blue"),
                false,
                false
            ),
        ];

        self::assertEquals($collection, $this->retriever->retrieveProgramIncrementContent(202, $user));
    }
}
