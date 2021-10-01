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
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\CheckFeatureIsPlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyCanBePlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanPlanInProgramIncrementStub;
use function PHPUnit\Framework\assertTrue;

final class ContentModifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID = 128;
    private UserIdentifier $user;
    private VerifyPrioritizeFeaturesPermissionStub $prioritize_permission_verifier;
    private VerifyIsVisibleFeatureStub $visible_feature_verifier;
    private VerifyCanBePlannedInProgramIncrementStub $can_be_planned_verifier;
    private OrderFeatureRank $feature_reorderer;
    private CheckFeatureIsPlannedInProgramIncrementStub $feature_is_planned_checker;

    protected function setUp(): void
    {
        $this->user                           = UserIdentifierStub::buildGenericUser();
        $this->prioritize_permission_verifier = VerifyPrioritizeFeaturesPermissionStub::canPrioritize();
        $this->visible_feature_verifier       = VerifyIsVisibleFeatureStub::buildVisibleFeature();
        $this->can_be_planned_verifier        = VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier();
        $this->feature_is_planned_checker     = CheckFeatureIsPlannedInProgramIncrementStub::buildPlannedFeature();
        $this->feature_reorderer              = $this->getStubOrderFeature();
    }

    public function getModifier(): ContentModifier
    {
        return new ContentModifier(
            $this->prioritize_permission_verifier,
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            $this->visible_feature_verifier,
            $this->can_be_planned_verifier,
            new FeaturePlanner(
                VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
                $this->buildFeatureRemoverStub(),
                $this->buildTopBacklogStoreStub(),
                $this->buildFeatureAdderStub()
            ),
            $this->feature_reorderer,
            $this->feature_is_planned_checker,
            VerifyUserCanPlanInProgramIncrementStub::buildCanPlan(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            RetrieveProgramOfProgramIncrementStub::withProgram(self::PROGRAM_ID),
            BuildProgramStub::stubValidProgram()
        );
    }

    public function testItThrowsWhenUserCannotPrioritizeFeatures(): void
    {
        $this->prioritize_permission_verifier = VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize();

        $this->expectException(NotAllowedToPrioritizeException::class);
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(201, null),
            $this->user
        );
    }

    public function testItThrowsWhenUserCannotSeeFeatureToAdd(): void
    {
        $this->visible_feature_verifier = VerifyIsVisibleFeatureStub::withNotVisibleFeature();

        $this->expectException(FeatureNotFoundException::class);
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(404, null),
            $this->user
        );
    }

    public function testItThrowsWhenFeatureToAddCannotBePlanned(): void
    {
        $this->can_be_planned_verifier = VerifyCanBePlannedInProgramIncrementStub::buildNotPlannableVerifier();

        $this->expectException(FeatureCannotBePlannedInProgramIncrementException::class);
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(404, null),
            $this->user
        );
    }

    public function testItSucceedsWhenThereIsOnlyFeatureToAdd(): void
    {
        $this->expectNotToPerformAssertions();
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(201, null),
            $this->user
        );
    }

    public function testItFailedWhenThereIsNoFeatureToAddOrToOrder(): void
    {
        $this->expectException(AddOrOrderMustBeSetException::class);
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(null, null),
            $this->user
        );
    }

    public function testItSucceedsWhenThereIsOnlyFeatureToReorder(): void
    {
        $this->feature_reorderer = $this->getStubOrderFeature(true);
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(null, $this->getFeatureElementToOrderRepresentation(201, 2020)),
            $this->user
        );
    }

    public function testItThrowsWhenFeatureToReorderIsNotInPlan(): void
    {
        $this->can_be_planned_verifier = VerifyCanBePlannedInProgramIncrementStub::buildNotPlannableVerifier();

        $this->expectException(InvalidFeatureIdInProgramIncrementException::class);
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(null, $this->getFeatureElementToOrderRepresentation(201, 2020)),
            $this->user
        );
    }

    public function testItThrowsWhenFeatureToReorderIsNotInProgramIncrement(): void
    {
        $this->feature_is_planned_checker = CheckFeatureIsPlannedInProgramIncrementStub::buildUnPlannedFeature();

        $this->expectException(InvalidFeatureIdInProgramIncrementException::class);
        $this->getModifier()->modifyContent(
            12,
            ContentChange::fromRESTRepresentation(null, $this->getFeatureElementToOrderRepresentation(201, 2020)),
            $this->user
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
        return new class ($is_called, self::PROGRAM_ID) implements OrderFeatureRank {
            public function __construct(private bool $is_called, private int $expected_program)
            {
            }

            public function reorder(
                FeatureElementToOrderInvolvedInChangeRepresentation $order,
                string $context_id,
                ProgramIdentifier $program
            ): void {
                if ($this->is_called) {
                    assertTrue($context_id === "12");
                    assertTrue($program->getId() === $this->expected_program);
                }
            }
        };
    }
}
