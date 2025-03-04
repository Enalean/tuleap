<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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

namespace Tuleap\REST;

use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RESTCollectionTransformerTest extends TestCase
{
    public function testFlattenCollection(): void
    {
        $flat_collection = RESTCollectionTransformer::flattenRepresentations(
            [
                ['a' => ['b' => 1]],
                ['a' => ['b' => 3]],
                ['a' => ['b' => 2]],
            ],
            self::flattener(...),
        );

        self::assertEquals(
            [['v' => 1], ['v' => 3], ['v' => 2]],
            $flat_collection,
        );
    }

    public function testThrows400WhenCannotProcessCollection(): void
    {
        $expected_error_message = 'Cannot transform representation';
        $flattener_with_error   = static fn(): Err => Result::err(Fault::fromMessage($expected_error_message));


        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage($expected_error_message);
        RESTCollectionTransformer::flattenRepresentations([['a' => 1]], $flattener_with_error);
    }

    /**
     * @psalm-param array{"a":array{"b": int}} $representation
     * @psalm-return Ok<array{"v":int}>
     */
    private static function flattener(array $representation): Ok
    {
        return Result::ok(['v' => $representation['a']['b']]);
    }
}
