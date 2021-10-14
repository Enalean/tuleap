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

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content\FeatureRemovalProcessor;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsVisibleFeatureAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\SearchProgramIncrementLinkedToFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogStore;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\OrderFeatureRankStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramIncrementLinkedToFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProcessTopBacklogChangeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;
    private RetrieveUserStub $retrieve_user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TopBacklogStore
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    private SearchProgramIncrementLinkedToFeature $program_increment_dao;
    private OrderFeatureRankStub $feature_orderer;
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->artifact_factory      = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->dao                   = $this->createMock(TopBacklogStore::class);
        $this->artifact_link_updater = $this->createMock(ArtifactLinkUpdater::class);
        $this->program_increment_dao = SearchProgramIncrementLinkedToFeatureStub::withoutLink();
        $this->feature_orderer       = OrderFeatureRankStub::withCount();
        $this->user                  = $this->createMock(\PFUser::class);
        $this->user->method('isSuperUser')->willReturn(true);
        $this->user->method('isAdmin')->willReturn(true);
        $this->user->method('getId')->willReturn(101);
        $this->retrieve_user   = RetrieveUserStub::withUser($this->user);
        $this->user_identifier = UserIdentifierStub::withId(101);
    }

    public function testAddAThrowExceptionWhenFeatureCannotBeViewByUser(): void
    {
        $tracker      = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $artifact_741 = $this->mockAnArtifact(741, "My 741", $tracker);
        $artifact_742 = $this->mockAnArtifact(742, "My 742", $tracker);

        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturnMap([
            [$this->user, 741, $artifact_741],
            [$this->user, 742, $artifact_742],
            [$this->user, 789, null],
            [$this->user, 790, null],
        ]);

        $this->expectException(FeatureNotFoundException::class);
        $this->getProcessor()->processTopBacklogChangeForAProgram(
            ProgramIdentifierBuilder::buildWithId(102),
            new TopBacklogChange([742, 790], [741, 789], false, null),
            $this->user_identifier,
            null
        );
    }

    public function testRemoveWhenFeatureCannotBeViewByUserThenNothingHappens(): void
    {
        $tracker      = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $artifact_741 = $this->mockAnArtifact(741, "My 741", $tracker);

        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturnMap([
            [$this->user, 741, $artifact_741],
            [$this->user, 789, null],
        ]);

        $this->dao->expects(self::once())->method('removeArtifactsFromExplicitTopBacklog')->with([741]);

        $this->getProcessor()->processTopBacklogChangeForAProgram(
            ProgramIdentifierBuilder::buildWithId(102),
            new TopBacklogChange([], [741, 789], false, null),
            $this->user_identifier,
            null
        );
    }

    public function testAddAndRemoveThrowExceptionWhenFeatureThatAreNotPartOfTheRequestedProgram(): void
    {
        $tracker  = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 666, 'group_name' => "My project"]))->build();
        $artifact = $this->mockAnArtifact(964, "My 964", $tracker);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $this->dao->expects(self::never())->method('removeArtifactsFromExplicitTopBacklog');
        $this->expectException(FeatureNotFoundException::class);
        $this->getProcessor()->processTopBacklogChangeForAProgram(
            ProgramIdentifierBuilder::buildWithId(102),
            new TopBacklogChange([964], [963], false, null),
            $this->user_identifier,
            null
        );
    }

    public function testAddFeatureInTopBacklogAndRemoveLinkToProgramIncrement(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $feature = $this->mockAnArtifact(964, "My 964", $tracker);
        $this->artifact_factory->expects(self::once())->method('getArtifactByIdUserCanView')->with($this->user, 964)->willReturn($feature);
        $this->program_increment_dao = SearchProgramIncrementLinkedToFeatureStub::with([["id" => 63]]);
        $program_increment           = $this->createMock(Artifact::class);
        $this->artifact_factory->expects(self::once())->method('getArtifactById')->with(63)->willReturn($program_increment);

        $this->dao->expects(self::once())->method('addArtifactsToTheExplicitTopBacklog');
        $this->artifact_link_updater->expects(self::once())->method("updateArtifactLinks")->with($this->user, $program_increment, [], [964], "");

        $this->getProcessor()->processTopBacklogChangeForAProgram(
            ProgramIdentifierBuilder::buildWithId(102),
            new TopBacklogChange([964], [], true, null),
            $this->user_identifier,
            null
        );
    }

    public function testDontAddFeatureInBacklogIfUserStoriesAreLinkedAndThrowException(): void
    {
        $process_top_backlog_change = new ProcessTopBacklogChange(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->feature_orderer,
            VerifyLinkedUserStoryIsNotPlannedStub::buildLinkedStories(),
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory, $this->retrieve_user),
            new FeatureRemovalProcessor($this->program_increment_dao, $this->artifact_factory, $this->artifact_link_updater, $this->retrieve_user)
        );

        $tracker = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $feature = $this->mockAnArtifact(964, "My 964", $tracker);
        $this->artifact_factory->expects(self::once())->method('getArtifactByIdUserCanView')->with($this->user, 964)->willReturn($feature);

        $this->artifact_factory->expects(self::never())->method('getArtifactById');

        $this->dao->expects(self::never())->method('addArtifactsToTheExplicitTopBacklog');
        $this->artifact_link_updater->expects(self::never())->method("updateArtifactLinks");

        $this->expectException(FeatureHasPlannedUserStoryException::class);

        $process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifierBuilder::buildWithId(102),
            new TopBacklogChange([964], [], true, null),
            $this->user_identifier,
            null
        );
    }

    public function testUserThatCannotPrioritizeFeaturesCannotAskForATopBacklogChange(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $user->method('isAdmin')->willReturn(false);
        $user->method('getId')->willReturn(101);
        $retrieve_user              = RetrieveUserStub::withUser($this->user);
        $process_top_backlog_change = new ProcessTopBacklogChange(
            VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize(),
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->feature_orderer,
            VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory, $retrieve_user),
            new FeatureRemovalProcessor($this->program_increment_dao, $this->artifact_factory, $this->artifact_link_updater, $retrieve_user),
        );
        $this->dao->expects(self::never())->method('removeArtifactsFromExplicitTopBacklog');

        $this->expectException(CannotManipulateTopBacklog::class);
        $process_top_backlog_change->processTopBacklogChangeForAProgram(
            ProgramIdentifierBuilder::buildWithId(102),
            new TopBacklogChange([], [403], false, null),
            $this->user_identifier,
            null
        );
    }

    public function testUserCanReorderTheBacklog(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(\Tracker::class);
        $tracker->method('getGroupId')->willReturn(666);
        $artifact->method('getTracker')->willReturn($tracker);
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        $this->dao->expects(self::never())->method('removeArtifactsFromExplicitTopBacklog');

        $element_to_order = new FeatureElementToOrderInvolvedInChangeRepresentation([964], "before", 900);

        $feature_reorder = FeaturesToReorderProxy::buildFromRESTRepresentation($element_to_order);

        $program = ProgramIdentifierBuilder::buildWithId(666);

        $this->getProcessor()->processTopBacklogChangeForAProgram(
            $program,
            new TopBacklogChange([], [], false, $feature_reorder),
            $this->user_identifier,
            null
        );

        self::assertEquals(1, $this->feature_orderer->getCallCount());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&Artifact
     */
    private function mockAnArtifact(int $id, string $title, \Tracker $tracker)
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn($id);
        $artifact->method('getTitle')->willReturn($title);

        return $artifact;
    }

    private function getProcessor(): ProcessTopBacklogChange
    {
        return new ProcessTopBacklogChange(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->feature_orderer,
            VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory, $this->retrieve_user),
            new FeatureRemovalProcessor(
                $this->program_increment_dao,
                $this->artifact_factory,
                $this->artifact_link_updater,
                $this->retrieve_user
            ),
        );
    }
}
