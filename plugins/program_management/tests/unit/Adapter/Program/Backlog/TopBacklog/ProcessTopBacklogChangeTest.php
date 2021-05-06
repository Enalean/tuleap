<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Rank\FeaturesRankOrderer;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsVisibleFeatureAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogStore;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProcessTopBacklogChangeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|FeaturesRankOrderer
     */
    private $feature_orderer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PrioritizeFeaturesPermissionVerifier
     */
    private $permissions_verifier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TopBacklogStore
     */
    private $dao;
    /**
     * @var ProcessTopBacklogChange
     */
    private $process_top_backlog_change;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramIncrementsDAO
     */
    private $program_increment_dao;

    protected function setUp(): void
    {
        $this->artifact_factory      = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->permissions_verifier  = \Mockery::mock(PrioritizeFeaturesPermissionVerifier::class);
        $this->dao                   = \Mockery::mock(TopBacklogStore::class);
        $this->artifact_link_updater = \Mockery::mock(ArtifactLinkUpdater::class);
        $this->program_increment_dao = \Mockery::mock(ProgramIncrementsDAO::class);
        $this->feature_orderer       = \Mockery::mock(FeaturesRankOrderer::class);

        $this->process_top_backlog_change = new ProcessTopBacklogChange(
            $this->permissions_verifier,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->feature_orderer,
            VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory),
            new FeatureRemovalProcessor($this->program_increment_dao, $this->artifact_factory, $this->artifact_link_updater),
        );
    }

    public function testAddAThrowExceptionWhenFeatureCannotBeViewByUser(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $tracker      = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $artifact_741 = $this->mockAnArtifact(741, "My 741", $tracker);
        $artifact_742 = $this->mockAnArtifact(742, "My 742", $tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 741)->andReturn($artifact_741);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 742)->andReturn($artifact_742);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 789)->andReturn(null);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 790)->andReturn(null);

        $this->expectException(FeatureNotFoundException::class);
        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user),
            new TopBacklogChange([742, 790], [741, 789], false, null),
            $user
        );
    }

    public function testRemoveWhenFeatureCannotBeViewByUserThenNothingHappens(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $tracker      = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $artifact_741 = $this->mockAnArtifact(741, "My 741", $tracker);
        $artifact_742 = $this->mockAnArtifact(742, "My 742", $tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 741)->andReturn($artifact_741);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 789)->andReturn(null);

        $this->dao->shouldReceive('removeArtifactsFromExplicitTopBacklog')->with([741])->once();

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user),
            new TopBacklogChange([], [741, 789], false, null),
            $user
        );
    }

    public function testAddAndRemoveThrowExceptionWhenFeatureThatAreNotPartOfTheRequestedProgram(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $tracker  = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 666, 'group_name' => "My project"]))->build();
        $artifact = $this->mockAnArtifact(964, "My 964", $tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn($artifact);

        $this->dao->shouldNotReceive('removeArtifactsFromExplicitTopBacklog');
        $this->expectException(FeatureNotFoundException::class);
        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user),
            new TopBacklogChange([964], [963], false, null),
            $user
        );
    }

    public function testAddFeatureInTopBacklogAndRemoveLinkToProgramIncrement(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true)->once();
        $user = UserTestBuilder::aUser()->build();

        $tracker = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $feature = $this->mockAnArtifact(964, "My 964", $tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 964)->andReturn($feature)->once();

        $this->program_increment_dao->shouldReceive("getProgramIncrementsLinkToFeatureId")->with(964)->once()->andReturn([["id" => 63]]);
        $program_increment = \Mockery::mock(Artifact::class);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(63)->andReturn($program_increment)->once();

        $this->dao->shouldReceive('addArtifactsToTheExplicitTopBacklog')->once();
        $this->artifact_link_updater->shouldReceive("updateArtifactLinks")->once()->with($user, $program_increment, [], [964], "");

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user),
            new TopBacklogChange([964], [], true, null),
            $user
        );
    }

    public function testDontAddFeatureInBacklogIfUserStoriesAreLinkedAndThrowException(): void
    {
        $this->process_top_backlog_change = new ProcessTopBacklogChange(
            $this->permissions_verifier,
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->feature_orderer,
            VerifyLinkedUserStoryIsNotPlannedStub::buildLinkedStories(),
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory),
            new FeatureRemovalProcessor($this->program_increment_dao, $this->artifact_factory, $this->artifact_link_updater)
        );

        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true)->once();
        $user = UserTestBuilder::aUser()->build();

        $tracker = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $feature = $this->mockAnArtifact(964, "My 964", $tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 964)->andReturn($feature)->once();

        $this->program_increment_dao->shouldReceive("getProgramIncrementsLinkToFeatureId")->never();
        $this->artifact_factory->shouldReceive('getArtifactById')->never();

        $this->dao->shouldReceive('addArtifactsToTheExplicitTopBacklog')->never();
        $this->artifact_link_updater->shouldReceive("updateArtifactLinks")->never();

        $this->expectException(FeatureHasPlannedUserStoryException::class);

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user),
            new TopBacklogChange([964], [], true, null),
            $user
        );
    }

    public function testUserThatCannotPrioritizeFeaturesCannotAskForATopBacklogChange(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(false);
        $user = UserTestBuilder::aUser()->build();
        $this->dao->shouldNotReceive('removeArtifactsFromExplicitTopBacklog');

        $this->expectException(CannotManipulateTopBacklog::class);
        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, $user),
            new TopBacklogChange([], [403], false, null),
            UserTestBuilder::aUser()->build()
        );
    }

    public function testUserCanReorderTheBacklog(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $artifact = \Mockery::mock(Artifact::class);
        $tracker  = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(666);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn($artifact);

        $this->dao->shouldNotReceive('removeArtifactsFromExplicitTopBacklog');

        $element_to_order              =  new FeatureElementToOrderInvolvedInChangeRepresentation();
        $element_to_order->ids         = [964];
        $element_to_order->direction   = "before";
        $element_to_order->compared_to = 900;

        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 666, $user);

        $this->feature_orderer->shouldReceive('reorder')->with($element_to_order, $program->getId(), $program)->once();

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            $program,
            new TopBacklogChange(
                [],
                [],
                false,
                $element_to_order
            ),
            $user
        );
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Artifact
     */
    private function mockAnArtifact(int $id, string $title, \Tracker $tracker)
    {
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getId')->andReturn($id);
        $artifact->shouldReceive('getTitle')->andReturn($title);

        return $artifact;
    }
}
