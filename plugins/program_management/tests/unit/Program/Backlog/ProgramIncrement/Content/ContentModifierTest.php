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
use Tuleap\ProgramManagement\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\PlannedProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Plan\VerifyCanBePlannedInProgramIncrement;
use Tuleap\ProgramManagement\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Program\ProgramSearcher;
use Tuleap\ProgramManagement\Program\SearchProgram;
use Tuleap\Test\Builders\UserTestBuilder;

final class ContentModifierTest extends TestCase
{
    public function testItThrowsWhenUserCannotPrioritizeFeatures(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(false),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            $this->getStubPlanVerifier()
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectException(NotAllowedToPrioritizeException::class);
        $modifier->modifyContent($user, 12, new ContentChange(201));
    }

    public function testItThrowsWhenFeatureToAddCannotBePlanned(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            $this->getStubPlanVerifier(false)
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectException(FeatureCannotBePlannedInProgramIncrementException::class);
        $modifier->modifyContent($user, 12, new ContentChange(404));
    }

    public function testItSucceeds(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            $this->getStubPlanVerifier()
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectNotToPerformAssertions();
        $modifier->modifyContent($user, 12, new ContentChange(201));
    }

    public function testItSucceedsWhenThereIsNoFeatureToAdd(): void
    {
        $modifier = new ContentModifier(
            $this->getStubPermissionVerifier(),
            $this->getStubProgramIncrementRetriever(),
            $this->getStubProgramSearcher(),
            $this->getStubPlanVerifier()
        );

        $user = UserTestBuilder::aUser()->build();

        $this->expectNotToPerformAssertions();
        $modifier->modifyContent($user, 12, new ContentChange(null));
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

    private function getStubPlanVerifier($can_plan = true): VerifyCanBePlannedInProgramIncrement
    {
        return new class ($can_plan) implements VerifyCanBePlannedInProgramIncrement {
            /** @var bool */
            private $can_plan;

            public function __construct(bool $can_plan)
            {
                $this->can_plan = $can_plan;
            }

            public function canBePlannedInProgramIncrement(int $feature_id, int $program_increment_id): bool
            {
                return $this->can_plan;
            }
        };
    }
}
