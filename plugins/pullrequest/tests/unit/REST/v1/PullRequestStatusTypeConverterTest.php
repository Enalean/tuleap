<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PullRequestStatusTypeConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @testWith [1, "update"]
     *           [2, "rebase"]
     *           [3, "merge"]
     *           [4, "abandon"]
     *           [5, "reopen"]
     */
    public function testItConvertsIntStatusToStringStatus(int $status_int, string $expected_string_status): void
    {
        $status = PullRequestStatusTypeConverter::fromIntStatusToStringStatus($status_int);

        self::assertEquals($expected_string_status, $status);
    }
}
