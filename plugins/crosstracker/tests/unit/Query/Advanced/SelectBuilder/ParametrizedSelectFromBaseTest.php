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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ParametrizedSelectFromBaseTest extends TestCase
{
    public function testItMergeAllSelects(): void
    {
        $base = new ParametrizedSelectFromAndWhereBase();
        $base->addSelect('1');
        $base->addSelect('2');

        self::assertEquals('1, 2', $base->getSelect());
    }

    public function testItSkipsEmptySelect(): void
    {
        $base = new ParametrizedSelectFromAndWhereBase();
        $base->addSelect('1');
        $base->addSelect('');
        $base->addSelect('2');

        self::assertEquals('1, 2', $base->getSelect());
    }

    public function testItMergeAllFrom(): void
    {
        $base = new ParametrizedSelectFromAndWhereBase();
        $base->addFrom('1', ['a']);
        $base->addFrom('2', ['b']);

        self::assertEquals("1\n2", $base->getFrom());
        self::assertEquals(['a', 'b'], $base->getFromParameters());
    }

    public function testItSkipsEmptyFrom(): void
    {
        $base = new ParametrizedSelectFromAndWhereBase();
        $base->addFrom('1', ['a']);
        $base->addFrom('', ['b']);
        $base->addFrom('2', ['c']);

        self::assertEquals("1\n2", $base->getFrom());
        self::assertEquals(['a', 'c'], $base->getFromParameters());
    }
}
