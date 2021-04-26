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

namespace Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content;

use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\CheckFeatureIsPlannedInProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\PlannedProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogStore;
use Tuleap\ProgramManagement\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Plan\InvalidFeatureIdInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Program\ProgramSearcher;
use Tuleap\ProgramManagement\Program\SearchProgram;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;
use Tuleap\ProgramManagement\Stub\VerifyCanBePlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use function PHPUnit\Framework\assertTrue;

final class ContentModifierTest extends TestCase
{
    public function testItThrowsWhenUserCannotPrioritizeFeatures(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(false),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(),
            new VerifyCanBePlannedInProgramIncrementStub(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(),
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectException(NotAllowedToPrioritizeException::class);
        $modifier->modifyContent($user, 12, new ContentChange(201, null));
    }

    public function testItThrowsWhenUserCannotSeeFeatureToAdd(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(false),
            new VerifyCanBePlannedInProgramIncrementStub(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(),
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectException(FeatureNotFoundException::class);
        $modifier->modifyContent($user, 12, new ContentChange(404, null));
    }

    public function testItThrowsWhenFeatureToAddCannotBePlanned(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(),
            new VerifyCanBePlannedInProgramIncrementStub(false),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(),
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectException(FeatureCannotBePlannedInProgramIncrementException::class);
        $modifier->modifyContent($user, 12, new ContentChange(404, null));
    }

    public function testItSucceedsWhenThereIsOnlyFeatureToAdd(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(),
            new VerifyCanBePlannedInProgramIncrementStub(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(),
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectNotToPerformAssertions();
        $modifier->modifyContent($user, 12, new ContentChange(201, null));
    }

    public function testItFailedWhenThereIsNoFeatureToAddOrToOrder(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(),
            new VerifyCanBePlannedInProgramIncrementStub(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(),
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectException(AddOrOrderMustBeSetException::class);
        $modifier->modifyContent($user, 12, new ContentChange(null, null));
    }

    public function testItSucceedsWhenThereIsOnlyFeatureToReorder(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(),
            new VerifyCanBePlannedInProgramIncrementStub(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(true),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(),
        );

        $user = UserTestBuilder::aUser()->build();

        $modifier->modifyContent(
            $user,
            12,
            new ContentChange(null, $this->getFeatureElementToOrderRepresentation(201, 2020))
        );
    }

    public function testItThrowsWhenFeatureToReorderIsNotInPlan(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(),
            new VerifyCanBePlannedInProgramIncrementStub(false),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(),
        );

        $user = UserTestBuilder::aUser()->build();
        $this->expectException(InvalidFeatureIdInProgramIncrementException::class);
        $modifier->modifyContent(
            $user,
            12,
            new ContentChange(null, $this->getFeatureElementToOrderRepresentation(201, 2020))
        );
    }

    public function testItThrowsWhenFeatureToReorderIsNotInProgramIncrement(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            new VerifyIsVisibleFeatureStub(),
            new VerifyCanBePlannedInProgramIncrementStub(),
            $this->buildFeaturePlanner(),
            $this->getStubOrderFeature(),
            $this->getStubCheckFeatureIsPlannedInProgramIncrement(false),
        );

        $user = UserTestBuilder::aUser()->build();
        $this->expectException(InvalidFeatureIdInProgramIncrementException::class);
        $modifier->modifyContent(
            $user,
            12,
            new ContentChange(null, $this->getFeatureElementToOrderRepresentation(201, 2020))
        );
    }

    private function getFeatureElementToOrderRepresentation(int $id, int $compared_to_id, string $direction = "before"): FeatureElementToOrderInvolvedInChangeRepresentation
    {
        $feature_to_order = new FeatureElementToOrderInvolvedInChangeRepresentation();

        $feature_to_order->ids         = [$id];
        $feature_to_order->compared_to = $compared_to_id;
        $feature_to_order->direction   = $direction;
        return $feature_to_order;
    }

    private function getStubCheckFeatureIsPlannedInProgramIncrement(bool $is_planned = true): CheckFeatureIsPlannedInProgramIncrement
    {
        return new class ($is_planned) implements CheckFeatureIsPlannedInProgramIncrement {

            /** @var bool */
            private $is_planned;

            public function __construct(bool $is_planned)
            {
                $this->is_planned = $is_planned;
            }

            public function isFeaturePlannedInProgramIncrement(int $program_increment_id, int $feature_id): bool
            {
                return $this->is_planned;
            }
        };
    }

    private function getStubPermissionVerifier($is_authorized = true): VerifyPrioritizeFeaturesPermission
    {
        return new class ($is_authorized) implements VerifyPrioritizeFeaturesPermission {
            /** @var bool */
            private $is_authorized;

            public function __construct(bool $is_authorized)
            {
                $this->is_authorized = $is_authorized;
            }

            public function canUserPrioritizeFeatures(Program $program, \PFUser $user): bool
            {
                return $this->is_authorized;
            }
        };
    }

    private function getStubProgramSearcher(): ProgramSearcher
    {
        return new ProgramSearcher(
            new class implements SearchProgram {
                public function searchProgramOfProgramIncrement(int $program_increment_id): ?int
                {
                    return 101;
                }
            }
        );
    }

    private function getStubProgramIncrementRetriever(): RetrieveProgramIncrement
    {
        return new class implements RetrieveProgramIncrement {
            public function retrieveProgramIncrement(int $program_increment_id, \PFUser $user): PlannedProgramIncrement
            {
                return new PlannedProgramIncrement($program_increment_id);
            }
        };
    }

    private function buildFeaturePlanner(): FeaturePlanner
    {
        return new FeaturePlanner(
            new DBTransactionExecutorPassthrough(),
            new VerifyLinkedUserStoryIsNotPlannedStub(),
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

            public function removeArtifactsPlannedInAProgramIncrement(int $potential_program_increment_id): void
            {
                throw new \LogicException('This method is not supposed to be called in the test');
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
            public function reorder(FeatureElementToOrderInvolvedInChangeRepresentation $order, string $context_id, Program $program): void
            {
                if ($this->is_called) {
                    assertTrue($context_id === "12");
                    assertTrue($program->getId() === 101);
                }
            }
        };
    }
}
