<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use DateTime;
use DateTimeImmutable;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Timetracking\Tests\Stub\SaveQueryStub;

final class TimetrackingManagementWidgetSaverTest extends TestCase
{
    public function testItReturnsTrueWhenQueryWasSaved(): void
    {
        $dao = SaveQueryStub::build();

        $start_date_immutable = DateTimeImmutable::createFromFormat(DateTime::ATOM, '2024-06-26T15:46:00z');
        $end_date_immutable   = DateTimeImmutable::createFromFormat(DateTime::ATOM, '2024-06-27T15:46:00z');

        if (! $start_date_immutable || ! $end_date_immutable) {
            throw new \Exception('Unable to build a start_date or end_date.');
        }

        $result = (new TimetrackingManagementWidgetSaver($dao))->saveConfiguration(
            89,
            $start_date_immutable,
            $end_date_immutable,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value);
        self::assertTrue($dao->getHasBeenCalled());
    }
}
