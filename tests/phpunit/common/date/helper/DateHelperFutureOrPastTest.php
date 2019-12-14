<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
 * Copyright (c) Enalean, 2017-present. All rights reserved
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\common\date;

use DateHelper;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

final class DateHelperFutureOrPastTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    public function testDateInPast(): void
    {
        $GLOBALS['Language']->shouldReceive('getText')->once();
        $GLOBALS['Language']->shouldReceive('getText')->with('include_utils', 'time_ago', \Mockery::any())->ordered();
        DateHelper::timeAgoInWords($_SERVER['REQUEST_TIME'] - 500);
    }

    public function testDateInFuture()
    {
        $GLOBALS['Language']->shouldReceive('getText')->times(2);
        $GLOBALS['Language']->shouldReceive('getText')->with(
            'include_utils',
            'time_in_future',
            \Mockery::any()
        )->ordered();
        DateHelper::timeAgoInWords($_SERVER['REQUEST_TIME'] + 500);
    }
}
