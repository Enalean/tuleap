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

use Tuleap\AgileDashboard\Milestone\Criterion\Status\ISearchOnStatus;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusClosed;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;

class FilteringQueryParser
{
    private const STATUS_OPEN   = 'open';
    private const STATUS_CLOSED = 'closed';

    /**
     * @throws MalformedQueryParameterException
     */
    public function parse(string $query): FilteringQuery
    {
        if ($query === '') {
            return FilteringQuery::fromStatusQuery(new StatusAll());
        }

        $query_object = json_decode(stripslashes($query));

        if ($query_object === null) {
            throw MalformedQueryParameterException::invalidQueryParameter();
        }

        if ($query_object == new \stdClass()) {
            return FilteringQuery::fromStatusQuery(new StatusAll());
        }

        if (! isset($query_object->period) && ! isset($query_object->status)) {
            throw MalformedQueryParameterException::invalidQueryParameter();
        }

        if (isset($query_object->period, $query_object->status)) {
            throw MalformedQueryParameterException::invalidQueryParameter();
        }

        if (isset($query_object->period)) {
            return FilteringQuery::fromPeriodQuery($this->parsePeriodQuery($query_object));
        }

        return FilteringQuery::fromStatusQuery($this->parseStatusQuery($query_object));
    }

    /**
     * @throws MalformedQueryParameterException
     */
    private function parsePeriodQuery(object $query_object): PeriodQuery
    {
        if ($query_object->period === PeriodQuery::FUTURE) {
            return PeriodQuery::createFuture();
        }

        if ($query_object->period === PeriodQuery::CURRENT) {
            return PeriodQuery::createCurrent();
        }

        throw MalformedQueryParameterException::invalidQueryPeriodParameter();
    }

    /**
     * @throws MalformedQueryParameterException
     */
    private function parseStatusQuery(object $query_object): ISearchOnStatus
    {
        if ($query_object->status === self::STATUS_OPEN) {
            return new StatusOpen();
        }
        if ($query_object->status === self::STATUS_CLOSED) {
            return new StatusClosed();
        }

        throw MalformedQueryParameterException::invalidQueryStatusParameter();
    }
}
