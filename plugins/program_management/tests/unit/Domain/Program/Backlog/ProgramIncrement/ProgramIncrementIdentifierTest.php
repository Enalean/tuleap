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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramIncrementIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsAnExceptionWhenTrackerIsNotValid(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $this->expectException(ProgramIncrementNotFoundException::class);
        ProgramIncrementIdentifier::fromId(CheckProgramIncrementStub::buildOtherArtifactChecker(), 1, $user);
    }

    public function testItBuildAProgramIncrement(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $tracker = ProgramIncrementIdentifier::fromId(CheckProgramIncrementStub::buildProgramIncrementChecker(), 1, $user);
        self::assertEquals(1, $tracker->getId());
    }
}
