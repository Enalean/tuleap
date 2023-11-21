<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap;

use Tuleap\Test\Builders\UserTestBuilder;

class TimezoneRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItGetsUserTimezone(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $user->setTimezone('America/Montreal');

        $timezone = TimezoneRetriever::getUserTimezone($user);
        self::assertEquals('America/Montreal', $timezone);
    }

    public function testItGetsServerTimezoneWhenUserTimezoneIsNotValid(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $user->setTimezone('this_is_not_a_valid_timezone');

        $timezone = TimezoneRetriever::getUserTimezone($user);
        self::assertEquals(TimezoneRetriever::getServerTimezone(), $timezone);
    }
}
