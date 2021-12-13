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
use Tuleap\ProgramManagement\Tests\Stub\VerifyHasAtLeastOnePlannedUserStoryStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProcessTopBacklogChangeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;
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
        $this->user                  = UserTestBuilder::buildWithDefaults();
        $this->retrieve_user         = RetrieveUserStub::withUser($this->user);
        $this->user_identifier       = UserIdentifierStub::withId(101);
    }

    public function testAddAThrowExceptionWhenFeatureCannotBeViewByUser(): void
    {
        $tracker      = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $artifact_741 = ArtifactTestBuilder::anArtifact(741)->withTitle("My 741")->inTracker($tracker)->build();
        $artifact_742 = ArtifactTestBuilder::anArtifact(742)->withTitle("My 742")->inTracker($tracker)->build();

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
        $artifact_741 = ArtifactTestBuilder::anArtifact(741)->withTitle("My 741")->inTracker($tracker)->build();

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
        $artifact = ArtifactTestBuilder::anArtifact(964)->withTitle("My 964")->inTracker($tracker)->build();
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
        $feature = ArtifactTestBuilder::anArtifact(964)->withTitle("My 964")->inTracker($tracker)->build();
        $this->artifact_factory->expects(self::once())->method('getArtifactByIdUserCanView')->with($this->user, 964)->willReturn($feature);
        $this->program_increment_dao = SearchProgramIncrementLinkedToFeatureStub::with([["id" => 63]]);
        $program_increment           = ArtifactTestBuilder::anArtifact(63)->build();
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
            VerifyHasAtLeastOnePlannedUserStoryStub::withPlannedUserStory(),
            new VerifyIsVisibleFeatureAdapter($this->artifact_factory, $this->retrieve_user),
            new FeatureRemovalProcessor($this->program_increment_dao, $this->artifact_factory, $this->artifact_link_updater, $this->retrieve_user)
        );

        $tracker = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 102, 'group_name' => "My project"]))->build();
        $feature = ArtifactTestBuilder::anArtifact(964)->withTitle("My 964")->inTracker($tracker)->build();
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
        $retrieve_user              = RetrieveUserStub::withUser($this->user);
        $process_top_backlog_change = new ProcessTopBacklogChange(
            VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize(),
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->feature_orderer,
            VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
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
        $tracker  = TrackerTestBuilder::aTracker()->withId(69)->withProject(new \Project(['group_id' => 666, 'group_name' => "My project"]))->build();
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
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

    private function getProcessor(): ProcessTopBacklogChange
    {
        return new ProcessTopBacklogChange(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->feature_orderer,
            VerifyHasAtLeastOnePlannedUserStoryStub::withNothingPlanned(),
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
