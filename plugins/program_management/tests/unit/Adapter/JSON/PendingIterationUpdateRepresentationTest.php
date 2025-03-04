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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\JSON;

use Tuleap\ProgramManagement\Tests\Builder\IterationUpdateBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PendingIterationUpdateRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID = 758;
    private const CHANGESET_ID = 3867;
    private const USER_ID      = 10;

    public function testItBuildsFromIterationUpdate(): void
    {
        $iteration_update = IterationUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::ITERATION_ID,
            15,
            self::CHANGESET_ID
        );

        $representation = PendingIterationUpdateRepresentation::fromIterationUpdate($iteration_update);

        self::assertSame(self::ITERATION_ID, $representation->iteration_id);
        self::assertSame(self::CHANGESET_ID, $representation->changeset_id);
        self::assertSame(self::USER_ID, $representation->user_id);
    }
}
