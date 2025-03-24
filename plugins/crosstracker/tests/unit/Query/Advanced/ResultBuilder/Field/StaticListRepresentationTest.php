<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field;

use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StaticListRepresentationTest extends TestCase
{
    public function testItContainsValues(): void
    {
        $collection = new StaticListRepresentation([
            new StaticListValueRepresentation('a', null),
            new StaticListValueRepresentation('B', null),
        ]);
        $values     = [
            new StaticListValueRepresentation('a', null),
            new StaticListValueRepresentation('B', null),
        ];
        self::assertEqualsCanonicalizing($values, $collection->value);
    }

    public function testItFiltersDuplicates(): void
    {
        $collection = new StaticListRepresentation([
            new StaticListValueRepresentation('a', null),
            new StaticListValueRepresentation('a', 'fiesta-red'),
            new StaticListValueRepresentation('B', null),
            new StaticListValueRepresentation('B', null),
            new StaticListValueRepresentation('a', null),
        ]);
        $values     = [
            new StaticListValueRepresentation('a', null),
            new StaticListValueRepresentation('a', 'fiesta-red'),
            new StaticListValueRepresentation('B', null),
        ];
        self::assertEqualsCanonicalizing($values, $collection->value);
    }
}
