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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class FeatureIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsNullWhenFeatureIsNotVisibleByUser(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);

        self::assertNull(FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildInvisibleFeature(), 404, $user, $program));
    }

    public function testItBuildsAValidFeature(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);

        $feature = FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildVisibleFeature(), 87, $user, $program);
        self::assertSame(87, $feature->id);
    }
}
