<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST;

use stdClass;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\ISearchOnStatus;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Request\MalformedQueryParameterException;

class QueryToCriterionOnlyAllStatusConverter
{
    public function convert(string $query): ISearchOnStatus
    {
        if ($query === '') {
            return new StatusOpen();
        }

        $query_object = json_decode(stripslashes($query));

        if (! is_object($query_object)) {
            throw MalformedQueryParameterException::invalidQueryOnlyAllStatusParameter();
        }

        if ($query_object == new stdClass()) {
            return new StatusOpen();
        }

        if (isset($query_object->status) && $query_object->status === 'all') {
            return new StatusAll();
        }


        throw MalformedQueryParameterException::invalidQueryOnlyAllStatusParameter();
    }
}
