<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\People;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SqlUnionTest extends TestCase
{
    public function testSqlUnion(): void
    {
        $union = new SqlUnion(
            new SqlQuery('a', 'b', 'c'),
            new SqlQuery('d', 'e', 'f'),
            new SqlQuery('e', 'g', 'h'),
        );

        self::assertSame('a UNION d UNION e', $union->sql);
        self::assertSame(['b', 'c', 'e', 'f', 'g', 'h'], $union->parameters);
    }
}
