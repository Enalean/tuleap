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

use PHPUnit\Framework\TestCase;

class TimezoneRetrieverTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItGetsUserTimezone(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isLoggedIn')->andReturns(true);
        $user->shouldReceive('getTimezone')->andReturns('America/Montreal');

        $timezone = TimezoneRetriever::getUserTimezone($user);
        $this->assertEquals('America/Montreal', $timezone);
    }

    public function testItGetsServerTimezoneWhenUserTimezoneIsNotValid(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isLoggedIn')->andReturns(true);
        $user->shouldReceive('getTimezone')->andReturns('this_is_not_a_valid_timezone');

        $timezone = TimezoneRetriever::getUserTimezone($user);
        $this->assertEquals(TimezoneRetriever::getServerTimezone(), $timezone);
    }
}
