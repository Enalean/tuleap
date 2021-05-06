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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\VerifyCanBePlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Stub\VerifyPrioritizeFeaturePermissionStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class FeatureAdditionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsWhenFeatureCannotBePlannedInProgramIncrement(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_increment = ProgramIncrementIdentifier::fromId(CheckProgramIncrementStub::buildProgramIncrementChecker(), 89, $user);
        $program           = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);
        $feature           = FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildVisibleFeature(), 127, $user, $program);

        $this->expectException(FeatureCannotBePlannedInProgramIncrementException::class);
        FeatureAddition::fromFeature(
            VerifyCanBePlannedInProgramIncrementStub::buildNotPlannableVerifier(),
            $feature,
            $program_increment,
            UserCanPrioritize::fromUser(VerifyPrioritizeFeaturePermissionStub::canPrioritize(), $user, $program)
        );
    }

    public function testItBuildsAValidPayload(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $program_increment   = ProgramIncrementIdentifier::fromId(CheckProgramIncrementStub::buildProgramIncrementChecker(), 89, $user);
        $program             = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);
        $feature             = FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildVisibleFeature(), 741, $user, $program);
        $user_can_prioritize = UserCanPrioritize::fromUser(VerifyPrioritizeFeaturePermissionStub::canPrioritize(), $user, $program);
        $payload             = FeatureAddition::fromFeature(
            VerifyCanBePlannedInProgramIncrementStub::buildCanBePlannedVerifier(),
            $feature,
            $program_increment,
            $user_can_prioritize
        );
        self::assertSame($feature, $payload->feature);
        self::assertSame($user_can_prioritize, $payload->user);
        self::assertSame($program_increment, $payload->program_increment);
    }
}
