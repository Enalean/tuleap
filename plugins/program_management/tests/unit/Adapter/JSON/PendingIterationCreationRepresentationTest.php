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

namespace Tuleap\ProgramManagement\Adapter\JSON;

use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PendingIterationCreationRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID = 758;
    private const CHANGESET_ID = 3867;

    public function testItBuildsFromIterationCreation(): void
    {
        $creation = IterationCreationBuilder::buildWithIds(
            self::ITERATION_ID,
            87,
            54,
            199,
            self::CHANGESET_ID
        );

        $presenter = PendingIterationCreationRepresentation::fromIterationCreation($creation);
        self::assertSame(self::ITERATION_ID, $presenter->id);
        self::assertSame(self::CHANGESET_ID, $presenter->changeset_id);
    }
}
