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
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusClosed;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\Test\Builders\UserTestBuilder;

final class RefinedTopMilestoneRequestTest extends TestCase
{
    public function testItCanBeBuiltWithoutAPeriodQuery(): void
    {
        $user         = UserTestBuilder::aUser()->build();
        $project      = \Project::buildForTest();
        $limit        = 50;
        $offset       = 50;
        $order        = 'asc';
        $raw_request  = new RawTopMilestoneRequest($user, $project, $limit, $offset, $order);
        $status_query = new StatusClosed();

        $request = RefinedTopMilestoneRequest::withStatusQuery($raw_request, $status_query);

        $this->assertFalse($request->shouldFilterCurrentMilestones());
        $this->assertFalse($request->shouldFilterFutureMilestones());
        $this->assertSame($user, $request->getUser());
        $this->assertSame($project, $request->getProject());
        $this->assertSame($limit, $request->getLimit());
        $this->assertSame($offset, $request->getOffset());
        $this->assertSame($order, $request->getOrder());
        $this->assertSame($status_query, $request->getStatusFilter());
    }

    public function testItCanBeBuiltWithAPeriodQuery(): void
    {
        $user         = UserTestBuilder::aUser()->build();
        $project      = \Project::buildForTest();
        $limit        = 25;
        $offset       = 0;
        $order        = 'desc';
        $raw_request  = new RawTopMilestoneRequest($user, $project, $limit, $offset, $order);
        $period_query = PeriodQuery::createFuture();

        $request = RefinedTopMilestoneRequest::withPeriodQuery($raw_request, $period_query);

        $this->assertSame($user, $request->getUser());
        $this->assertSame($project, $request->getProject());
        $this->assertSame($limit, $request->getLimit());
        $this->assertSame($offset, $request->getOffset());
        $this->assertSame($order, $request->getOrder());
        $this->assertInstanceOf(StatusOpen::class, $request->getStatusFilter());
    }

    public function testItCanBeBuiltWithAFuturePeriodQuery(): void
    {
        $user         = UserTestBuilder::aUser()->build();
        $project      = \Project::buildForTest();
        $raw_request  = new RawTopMilestoneRequest($user, $project, 25, 0, 'desc');
        $period_query = PeriodQuery::createFuture();

        $request = RefinedTopMilestoneRequest::withPeriodQuery($raw_request, $period_query);

        $this->assertFalse($request->shouldFilterCurrentMilestones());
        $this->assertTrue($request->shouldFilterFutureMilestones());
    }

    public function testItCanBeBuiltWithACurrentPeriodQuery(): void
    {
        $user         = UserTestBuilder::aUser()->build();
        $project      = \Project::buildForTest();
        $raw_request  = new RawTopMilestoneRequest($user, $project, 25, 0, 'desc');
        $period_query = PeriodQuery::createCurrent();

        $request = RefinedTopMilestoneRequest::withPeriodQuery($raw_request, $period_query);

        $this->assertTrue($request->shouldFilterCurrentMilestones());
        $this->assertFalse($request->shouldFilterFutureMilestones());
    }
}
