<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\User\REST\v1;

use Tuleap\Test\Builders\HistoryEntryBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserHistoryRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuilds(): void
    {
        $first_entry  = HistoryEntryBuilder::anEntryVisitedAt(1390469207)->build();
        $second_entry = HistoryEntryBuilder::anEntryVisitedAt(1960365651)->build();

        $representation = UserHistoryRepresentation::build($first_entry, $second_entry);
        self::assertCount(2, $representation->entries);
    }
}
