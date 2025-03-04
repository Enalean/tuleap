<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementInfo;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementInfoBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsProgramIncrementInfo(): void
    {
        $builder = new ProgramIncrementInfoBuilder(
            RetrieveProgramIncrementStub::withSuccessiveProgramIncrements(
                ProgramIncrementBuilder::buildWithId(1260)
            )
        );

        $increment_info = $builder->build(
            UserIdentifierStub::withId(666),
            ProgramIncrementIdentifierBuilder::buildWithId(1260)
        );

        self::assertEquals(
            ProgramIncrementInfo::fromIncrementInfo(
                1260,
                'Increment 1',
                'Sep 28',
                'Oct 28'
            ),
            $increment_info
        );
    }
}
