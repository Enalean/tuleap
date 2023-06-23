<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query;

use Tuleap\Test\PHPUnit\TestCase;

final class ParametrizedAndFromWhereTest extends TestCase
{
    public function testGetFromWhere(): void
    {
        $and = new ParametrizedAndFromWhere(
            new ParametrizedFromWhere('INNER JOIN left ON (left.col = ?)', 'left.col = ?', [1], [2]),
            new ParametrizedFromWhere('INNER JOIN right ON (right.col = ?)', 'right.col = ?', [3], [4]),
        );

        self::assertEquals(
            'INNER JOIN left ON (left.col = ?) INNER JOIN right ON (right.col = ?)',
            $and->getFrom(),
        );
        self::assertEquals(
            [1, 3],
            $and->getFromParameters(),
        );

        self::assertEquals(
            '(left.col = ?) AND (right.col = ?)',
            $and->getWhere(),
        );
        self::assertEquals(
            [2, 4],
            $and->getWhereParameters(),
        );

        $all_parametrized_from = $and->getAllParametrizedFrom();
        self::assertCount(2, $all_parametrized_from);
        self::assertEquals(
            'INNER JOIN left ON (left.col = ?)',
            $all_parametrized_from[0]->getFrom(),
        );
        self::assertEquals(
            'INNER JOIN right ON (right.col = ?)',
            $all_parametrized_from[1]->getFrom(),
        );
        self::assertEquals(
            [1],
            $all_parametrized_from[0]->getParameters(),
        );
        self::assertEquals(
            [3],
            $all_parametrized_from[1]->getParameters(),
        );
    }

    public function testGetImbricatedFromWhere(): void
    {
        $and = new ParametrizedAndFromWhere(
            new ParametrizedOrFromWhere(
                new ParametrizedFromWhere('INNER JOIN left1 ON (left1.col = ?)', 'left1.col = ?', [1], [2]),
                new ParametrizedFromWhere('INNER JOIN right1 ON (right1.col = ?)', 'right1.col = ?', [3], [4]),
            ),
            new ParametrizedOrFromWhere(
                new ParametrizedFromWhere('INNER JOIN left2 ON (left2.col = ?)', 'left2.col = ?', [5], [6]),
                new ParametrizedFromWhere('INNER JOIN right2 ON (right2.col = ?)', 'right2.col = ?', [7], [8]),
            ),
        );

        self::assertEquals(
            'INNER JOIN left1 ON (left1.col = ?) INNER JOIN right1 ON (right1.col = ?) INNER JOIN left2 ON (left2.col = ?) INNER JOIN right2 ON (right2.col = ?)',
            $and->getFrom(),
        );
        self::assertEquals(
            [1, 3, 5, 7],
            $and->getFromParameters(),
        );

        self::assertEquals(
            '(((left1.col = ?) OR (right1.col = ?))) AND (((left2.col = ?) OR (right2.col = ?)))',
            $and->getWhere(),
        );
        self::assertEquals(
            [2, 4, 6, 8],
            $and->getWhereParameters(),
        );

        $all_parametrized_from = $and->getAllParametrizedFrom();
        self::assertCount(4, $all_parametrized_from);
        self::assertEquals(
            'INNER JOIN left1 ON (left1.col = ?)',
            $all_parametrized_from[0]->getFrom(),
        );
        self::assertEquals(
            'INNER JOIN right1 ON (right1.col = ?)',
            $all_parametrized_from[1]->getFrom(),
        );
        self::assertEquals(
            'INNER JOIN left2 ON (left2.col = ?)',
            $all_parametrized_from[2]->getFrom(),
        );
        self::assertEquals(
            'INNER JOIN right2 ON (right2.col = ?)',
            $all_parametrized_from[3]->getFrom(),
        );
        self::assertEquals(
            [1],
            $all_parametrized_from[0]->getParameters(),
        );
        self::assertEquals(
            [3],
            $all_parametrized_from[1]->getParameters(),
        );
        self::assertEquals(
            [5],
            $all_parametrized_from[2]->getParameters(),
        );
        self::assertEquals(
            [7],
            $all_parametrized_from[3]->getParameters(),
        );
    }

    public function testWithFromParametrizedFromRight(): void
    {
        $and = new ParametrizedAndFromWhere(
            new ParametrizedFromWhere('INNER JOIN left ON (left.col = ?)', 'left.col = ?', [1], [2]),
            ParametrizedFromWhere::fromParametrizedFrom(new ParametrizedFrom('INNER JOIN right ON (right.col = ?)', [3])),
        );

        self::assertEquals(
            'INNER JOIN left ON (left.col = ?) INNER JOIN right ON (right.col = ?)',
            $and->getFrom(),
        );
        self::assertEquals(
            [1, 3],
            $and->getFromParameters(),
        );

        self::assertEquals(
            'left.col = ?',
            $and->getWhere(),
        );
        self::assertEquals(
            [2],
            $and->getWhereParameters(),
        );

        $all_parametrized_from = $and->getAllParametrizedFrom();
        self::assertCount(2, $all_parametrized_from);
        self::assertEquals(
            'INNER JOIN left ON (left.col = ?)',
            $all_parametrized_from[0]->getFrom(),
        );
        self::assertEquals(
            'INNER JOIN right ON (right.col = ?)',
            $all_parametrized_from[1]->getFrom(),
        );
        self::assertEquals(
            [1],
            $all_parametrized_from[0]->getParameters(),
        );
        self::assertEquals(
            [3],
            $all_parametrized_from[1]->getParameters(),
        );
    }

    public function testWithFromParametrizedFromLeft(): void
    {
        $and = new ParametrizedAndFromWhere(
            ParametrizedFromWhere::fromParametrizedFrom(new ParametrizedFrom('INNER JOIN left ON (left.col = ?)', [1])),
            new ParametrizedFromWhere('INNER JOIN right ON (right.col = ?)', 'right.col = ?', [3], [4]),
        );

        self::assertEquals(
            'INNER JOIN left ON (left.col = ?) INNER JOIN right ON (right.col = ?)',
            $and->getFrom(),
        );
        self::assertEquals(
            [1, 3],
            $and->getFromParameters(),
        );

        self::assertEquals(
            'right.col = ?',
            $and->getWhere(),
        );
        self::assertEquals(
            [4],
            $and->getWhereParameters(),
        );

        $all_parametrized_from = $and->getAllParametrizedFrom();
        self::assertCount(2, $all_parametrized_from);
        self::assertEquals(
            'INNER JOIN left ON (left.col = ?)',
            $all_parametrized_from[0]->getFrom(),
        );
        self::assertEquals(
            'INNER JOIN right ON (right.col = ?)',
            $all_parametrized_from[1]->getFrom(),
        );
        self::assertEquals(
            [1],
            $all_parametrized_from[0]->getParameters(),
        );
        self::assertEquals(
            [3],
            $all_parametrized_from[1]->getParameters(),
        );
    }

    public function testWithFromParametrizedFromLeftAndRight(): void
    {
        $and = new ParametrizedAndFromWhere(
            ParametrizedFromWhere::fromParametrizedFrom(new ParametrizedFrom('INNER JOIN left ON (left.col = ?)', [1])),
            ParametrizedFromWhere::fromParametrizedFrom(new ParametrizedFrom('INNER JOIN right ON (right.col = ?)', [3])),
        );

        self::assertEquals(
            'INNER JOIN left ON (left.col = ?) INNER JOIN right ON (right.col = ?)',
            $and->getFrom(),
        );
        self::assertEquals(
            [1, 3],
            $and->getFromParameters(),
        );

        self::assertEquals(
            '',
            $and->getWhere(),
        );
        self::assertEquals(
            [],
            $and->getWhereParameters(),
        );

        $all_parametrized_from = $and->getAllParametrizedFrom();
        self::assertCount(2, $all_parametrized_from);
        self::assertEquals(
            'INNER JOIN left ON (left.col = ?)',
            $all_parametrized_from[0]->getFrom(),
        );
        self::assertEquals(
            'INNER JOIN right ON (right.col = ?)',
            $all_parametrized_from[1]->getFrom(),
        );
        self::assertEquals(
            [1],
            $all_parametrized_from[0]->getParameters(),
        );
        self::assertEquals(
            [3],
            $all_parametrized_from[1]->getParameters(),
        );
    }
}
