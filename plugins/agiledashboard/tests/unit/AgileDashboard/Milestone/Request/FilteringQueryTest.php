<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Request;

use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;

final class FilteringQueryTest extends TestCase
{
    public function testCanBeBuiltFromStatusQuery(): void
    {
        $status = new StatusAll();
        $query  = FilteringQuery::fromStatusQuery($status);

        $this->assertSame($status, $query->getStatusFilter());
        $this->assertFalse($query->isCurrentPeriod());
        $this->assertFalse($query->isFuturePeriod());
    }

    public function testCanBeBuiltFromCurrentPeriodQuery(): void
    {
        $query = FilteringQuery::fromPeriodQuery(PeriodQuery::createCurrent());
        $this->assertTrue($query->isCurrentPeriod());
        $this->assertFalse($query->isFuturePeriod());
    }

    public function testCanBeBuiltFromFuturePeriodQuery(): void
    {
        $query = FilteringQuery::fromPeriodQuery(PeriodQuery::createFuture());
        $this->assertTrue($query->isFuturePeriod());
        $this->assertFalse($query->isCurrentPeriod());
    }
}
