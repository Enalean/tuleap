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
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\VerifyCanBePlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class FeatureAdditionTest extends TestCase
{
    public function testItThrowsWhenFeatureCannotBePlannedInProgramIncrement(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_increment = ProgramIncrementIdentifier::fromId(new CheckProgramIncrementStub(true), 89, $user);
        $program           = new Program(110);
        $feature           = FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(), 127, $user, $program);

        $this->expectException(FeatureCannotBePlannedInProgramIncrementException::class);
        FeatureAddition::fromFeature(
            new VerifyCanBePlannedInProgramIncrementStub(false),
            $feature,
            $program_increment,
            $user
        );
    }

    public function testItBuildsAValidPayload(): void
    {
        $user              = UserTestBuilder::aUser()->build();
        $program_increment = ProgramIncrementIdentifier::fromId(new CheckProgramIncrementStub(true), 89, $user);
        $program           = new Program(110);
        $feature           = FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(), 741, $user, $program);

        $payload = FeatureAddition::fromFeature(
            new VerifyCanBePlannedInProgramIncrementStub(),
            $feature,
            $program_increment,
            $user
        );
        self::assertSame($feature, $payload->feature);
        self::assertSame($user, $payload->user);
        self::assertSame($program_increment, $payload->program_increment);
    }
}
