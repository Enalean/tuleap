<?php
/**
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

use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TopMilestoneRequestTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsTrueIfItsFilteringQueryIsFuture(): void
    {
        $user            = UserTestBuilder::aUser()->build();
        $project         = ProjectTestBuilder::aProject()->build();
        $limit           = 50;
        $offset          = 50;
        $order           = 'asc';
        $filtering_query = FilteringQuery::fromPeriodQuery(PeriodQuery::createFuture());

        $request = new TopMilestoneRequest($user, $project, $limit, $offset, $order, $filtering_query);

        $this->assertTrue($request->shouldFilterFutureMilestones());
    }

    public function testItReturnsTrueIfItsFilteringQueryIsCurrent(): void
    {
        $user            = UserTestBuilder::aUser()->build();
        $project         = ProjectTestBuilder::aProject()->build();
        $limit           = 50;
        $offset          = 50;
        $order           = 'asc';
        $filtering_query = FilteringQuery::fromPeriodQuery(PeriodQuery::createCurrent());

        $request = new TopMilestoneRequest($user, $project, $limit, $offset, $order, $filtering_query);

        $this->assertTrue($request->shouldFilterCurrentMilestones());
    }

    public function testItReturnsItsComponents(): void
    {
        $user            = UserTestBuilder::aUser()->build();
        $project         = ProjectTestBuilder::aProject()->build();
        $limit           = 50;
        $offset          = 50;
        $order           = 'desc';
        $status_query    = new StatusAll();
        $filtering_query = FilteringQuery::fromStatusQuery($status_query);

        $request = new TopMilestoneRequest($user, $project, $limit, $offset, $order, $filtering_query);

        self::assertSame($user, $request->getUser());
        self::assertSame($project, $request->getProject());
        self::assertSame($limit, $request->getLimit());
        self::assertSame($offset, $request->getOffset());
        self::assertSame($order, $request->getOrder());
        self::assertSame($status_query, $request->getStatusFilter());
    }
}
