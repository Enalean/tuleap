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

namespace Adapter\Program\Feature;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Monolog\Test\TestCase;
use Project;
use Psr\Log\Test\TestLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxRetriever;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CheckProgramIncrement;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredTimebox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class UserStoriesInMirroredProgramIncrementsPlannerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ContentDao
     */
    private $content_dao;

    /**
     * @var UserStoriesInMirroredProgramIncrementsPlanner
     */
    private $planner;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|MirroredTimeboxRetriever
     */
    private $mirrored_milestone_retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PrioritizeFeaturesPermissionVerifier
     */
    private $prioritize_features_permission_verifier;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CheckProgramIncrement
     */
    private $retrieve_program_increment;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsLinkedToParentDao
     */
    private $artifacts_linked_dao;

    protected function setUp(): void
    {
        $db_transaction_executor            = new DBTransactionExecutorPassthrough();
        $this->artifacts_linked_dao         = \Mockery::mock(ArtifactsLinkedToParentDao::class);
        $this->tracker_artifact_factory     = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->mirrored_milestone_retriever = \Mockery::mock(MirroredTimeboxRetriever::class);
        $this->content_dao                  = \Mockery::mock(ContentDao::class);

        $this->planner = new UserStoriesInMirroredProgramIncrementsPlanner(
            $db_transaction_executor,
            $this->artifacts_linked_dao,
            $this->tracker_artifact_factory,
            $this->mirrored_milestone_retriever,
            $this->content_dao,
            new TestLogger()
        );
    }

    public function testItAddLinksToMirroredMilestones(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $change = new ProgramIncrementChanged(1, 10, $user);

        $feature_id = 1234;
        $raw_link   = ['id' => $feature_id, 'project_id' => 101];
        $this->content_dao->shouldReceive('searchContent')->once()
            ->andReturn([['artifact_id' => 101]]);
        $this->artifacts_linked_dao->shouldReceive('getArtifactsLinkedToId')
            ->once()
            ->andReturn([$raw_link]);

        $milestone_id = 666;
        $this->mirrored_milestone_retriever->shouldReceive('retrieveMilestonesLinkedTo')->with(1)
            ->once()->andReturn([new MirroredTimebox($milestone_id)]);

        $milestone = \Mockery::mock(Artifact::class);
        $milestone->shouldReceive('getId')->andReturn($milestone_id);
        $milestone->shouldReceive('getTracker')->andReturn(TrackerTestBuilder::aTracker()->withProject(Project::buildForTest())->build());
        $field_artifact_link = \Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $field_artifact_link->shouldReceive('getId')->andReturn(1);
        $milestone->shouldReceive('getAnArtifactLinkField')->andReturn($field_artifact_link);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->once()->with($milestone_id)->andReturn(
            $milestone
        );

        $this->artifacts_linked_dao->shouldReceive('getUserStoriesOfMirroredProgramIncrementThatAreNotLinkedToASprint')->andReturn(
            [['id' => 1234]]
        );

        $fields_data = new FieldData(
            [FeatureChange::fromRaw($raw_link)],
            [],
            1
        );

        $milestone->shouldReceive('createNewChangeset')->with($fields_data->getFieldDataForChangesetCreationFormat(101), "", $user)->once();

        $this->planner->plan($change);
    }

    public function testItDoesNothingWhenArtifactLinkIsNotFound(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $change = new ProgramIncrementChanged(1, 10, $user);

        $feature_id = 1234;
        $this->content_dao->shouldReceive('searchContent')->once()
            ->andReturn([['artifact_id' => 101]]);
        $this->artifacts_linked_dao->shouldReceive('getArtifactsLinkedToId')
            ->once()
            ->andReturn([['id' => $feature_id, 'project_id' => 101]]);

        $milestone_id = 666;
        $this->mirrored_milestone_retriever->shouldReceive('retrieveMilestonesLinkedTo')->with(1)
            ->once()->andReturn([new MirroredTimebox($milestone_id)]);

        $milestone = \Mockery::mock(Artifact::class);
        $milestone->shouldReceive('getAnArtifactLinkField')->andReturnNull();

        $this->tracker_artifact_factory->shouldReceive('getArtifactById')
            ->once()->with($milestone_id)->andReturn($milestone);

        $milestone->shouldReceive('createNewChangeset')->never();

        $this->planner->plan($change);
    }

    public function testItDoesNothingWhenMilestoneIsNotFound(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $change = new ProgramIncrementChanged(1, 10, $user);

        $feature_id = 1234;
        $this->content_dao->shouldReceive('searchContent')->once()
            ->andReturn([['artifact_id' => 101]]);
        $this->artifacts_linked_dao->shouldReceive('getArtifactsLinkedToId')
            ->once()
            ->andReturn([['id' => $feature_id, 'project_id' => 101]]);

        $milestone_id = 666;
        $this->mirrored_milestone_retriever->shouldReceive('retrieveMilestonesLinkedTo')->with(1)
            ->once()->andReturn([new MirroredTimebox($milestone_id)]);

        $this->tracker_artifact_factory->shouldReceive('getArtifactById')
            ->once()->with($milestone_id)->andReturn([]);

        $this->planner->plan($change);
    }

    public function testItDoesNotAddUserStoryIfUserStoryIsNotInProject(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $change = new ProgramIncrementChanged(1, 10, $user);

        $this->content_dao->shouldReceive('searchContent')->once()
            ->andReturn([['artifact_id' => 101]]);
        $this->artifacts_linked_dao->shouldReceive('getArtifactsLinkedToId')
            ->once()
            ->andReturn([['id' => 1234, 'project_id' => 122]]);

        $milestone_id = 666;
        $this->mirrored_milestone_retriever->shouldReceive('retrieveMilestonesLinkedTo')->with(1)
            ->once()->andReturn([new MirroredTimebox($milestone_id)]);

        $milestone = \Mockery::mock(Artifact::class);
        $milestone->shouldReceive('getId')->andReturn($milestone_id);
        $milestone->shouldReceive('getTracker')->andReturn(TrackerTestBuilder::aTracker()->withProject(Project::buildForTest())->build());
        $field_artifact_link = new Tracker_FormElement_Field_ArtifactLink(
            1,
            70,
            null,
            'field_artlink',
            'Field ArtLink',
            '',
            1,
            'P',
            true,
            '',
            1
        );
        $milestone->shouldReceive('getAnArtifactLinkField')->andReturn($field_artifact_link);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->once()->with($milestone_id)->andReturn(
            $milestone
        );

        $this->artifacts_linked_dao->shouldReceive('getUserStoriesOfMirroredProgramIncrementThatAreNotLinkedToASprint')->andReturn(
            [['id' => 1234]]
        );

        $fields_data = new FieldData(
            [],
            [],
            1
        );

        $milestone->shouldReceive('createNewChangeset')->with($fields_data->getFieldDataForChangesetCreationFormat(122), "", $user)->once();

        $this->planner->plan($change);
    }
}
