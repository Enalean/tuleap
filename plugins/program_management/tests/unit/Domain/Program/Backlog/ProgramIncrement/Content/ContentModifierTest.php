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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogStore;
use Tuleap\ProgramManagement\Domain\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\Plan\InvalidFeatureIdInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramSearcher;
use Tuleap\ProgramManagement\Domain\Program\SearchProgram;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\CheckFeatureIsPlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyCanBePlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanPlanInProgramIncrementStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use function PHPUnit\Framework\assertTrue;

final class ContentModifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsWhenUserCannotPrioritizeFeatures(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = $this->getAMockedUser(true);

        $this->expectException(NotAllowedToPrioritizeException::class);
        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(201, null),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItThrowsWhenUserCannotSeeFeatureToAdd(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::withNotVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = $this->getAMockedUser(false);
        $user->method('isAdmin')->willReturn(false);

        $this->expectException(FeatureNotFoundException::class);
        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(404, null),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItThrowsWhenFeatureToAddCannotBePlanned(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildNotPlannableVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = $this->getAMockedUser(true);

        $this->expectException(FeatureCannotBePlannedInProgramIncrementException::class);
        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(404, null),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItSucceedsWhenThereIsOnlyFeatureToAdd(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = $this->getAMockedUser(true);

        $this->expectNotToPerformAssertions();
        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(201, null),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItFailedWhenThereIsNoFeatureToAddOrToOrder(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectException(AddOrOrderMustBeSetException::class);
        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(null, null),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItSucceedsWhenThereIsOnlyFeatureToReorder(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(true),
            CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = $this->getAMockedUser(true);

        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(null, $this->getFeatureElementToOrderRepresentation(201, 2020)),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItThrowsWhenFeatureToReorderIsNotInPlan(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildNotPlannableVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = $this->getAMockedUser(true);
        $this->expectException(InvalidFeatureIdInProgramIncrementException::class);
        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(null, $this->getFeatureElementToOrderRepresentation(201, 2020)),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItThrowsWhenFeatureToReorderIsNotInProgramIncrement(): void
    {
        $modifier = new ContentModifier(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            $this->getStubProgramSearcher(),
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            CheckFeatureIsPlannedInProgramIncrementStub::buildUnPlannedFeature(),
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan()
        );

        $user = $this->getAMockedUser(true);
        $this->expectException(InvalidFeatureIdInProgramIncrementException::class);
        $modifier->modifyContent(
            $user,
            12,
            ContentChange::fromRESTRepresentation(null, $this->getFeatureElementToOrderRepresentation(201, 2020)),
            UserIdentifierStub::buildGenericUser()
        );
    }

    private function getFeatureElementToOrderRepresentation(
        int $id,
        int $compared_to_id,
        string $direction = "before"
    ): FeatureElementToOrderInvolvedInChangeRepresentation {
        $feature_to_order = new FeatureElementToOrderInvolvedInChangeRepresentation();

        $feature_to_order->ids         = [$id];
        $feature_to_order->compared_to = $compared_to_id;
        $feature_to_order->direction   = $direction;

        return $feature_to_order;
    }

    private function getStubProgramSearcher(): ProgramSearcher
    {
        return new ProgramSearcher(
            new class implements SearchProgram {
                public function searchProgramOfProgramIncrement(int $program_increment_id): ?int
                {
                    return 101;
                }
            },
            BuildProgramStub::stubValidProgram()
        );
    }

    private function buildFeaturePlanner(): FeaturePlanner
    {
        return new FeaturePlanner(
            new DBTransactionExecutorPassthrough(),
            VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
            $this->buildFeatureRemoverStub(),
            $this->buildTopBacklogStoreStub(),
            $this->buildFeatureAdderStub()
        );
    }

    private function buildFeatureRemoverStub(): RemoveFeature
    {
        return new class implements RemoveFeature {
            public function removeFromAllProgramIncrements(FeatureRemoval $feature_removal): void
            {
                // Side effects
            }
        };
    }

    private function buildTopBacklogStoreStub(): TopBacklogStore
    {
        return new class implements TopBacklogStore {
            public function isInTheExplicitTopBacklog(int $artifact_id): bool
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function addArtifactsToTheExplicitTopBacklog(array $artifact_ids): void
            {
                throw new \LogicException('This method is not supposed to be called in the test');
            }

            public function removeArtifactsFromExplicitTopBacklog(array $artifact_ids): void
            {
                // Side effects
            }
        };
    }

    private function buildFeatureAdderStub(): AddFeature
    {
        return new class implements AddFeature {
            public function add(FeatureAddition $feature_addition): void
            {
                // Side effects
            }
        };
    }

    private function getStubOrderFeature(bool $is_called = false): OrderFeatureRank
    {
        return new class ($is_called) implements OrderFeatureRank {

            /** @var bool */
            private $is_called;

            public function __construct(bool $is_called)
            {
                $this->is_called = $is_called;
            }

            public function reorder(
                FeatureElementToOrderInvolvedInChangeRepresentation $order,
                string $context_id,
                ProgramIdentifier $program
            ): void {
                if ($this->is_called) {
                    assertTrue($context_id === "12");
                    assertTrue($program->getId() === 101);
                }
            }
        };
    }

    /**
     * @return \PFUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getAMockedUser(bool $is_super_user)
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn($is_super_user);
        $user->method('isAdmin')->willReturn($is_super_user);
        $user->method('getId')->willReturn(101);

        return $user;
    }
}
