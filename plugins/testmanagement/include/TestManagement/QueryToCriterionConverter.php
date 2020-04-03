<?php
/**
 * Copyright (c) Enalean, 2016-present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Tuleap\TestManagement\Criterion\StatusAll;
use Tuleap\TestManagement\Criterion\StatusClosed;
use Tuleap\TestManagement\Criterion\StatusOpen;
use Tuleap\TestManagement\Criterion\MilestoneAll;
use Tuleap\TestManagement\Criterion\MilestoneFilter;

class QueryToCriterionConverter
{
    /**
     * @param string|null $query
     * @return StatusAll|StatusClosed|StatusOpen
     * @throws MalformedQueryParameterException
     */
    public function convertStatus($query)
    {
        $error_message = 'Expecting {"status":"open"} or {"status":"closed"}.';

        if (! isset($query)) {
            return new StatusAll();
        }

        $query_object = json_decode(stripslashes($query));

        if (! is_object($query_object)) {
            throw new MalformedQueryParameterException();
        }

        if (! isset($query_object->status)) {
            return new StatusAll();
        }

        if ($query_object->status === 'open') {
            return new StatusOpen();
        } elseif ($query_object->status === 'closed') {
            return new StatusClosed();
        } else {
            throw new MalformedQueryParameterException($error_message);
        }
    }

    /**
     * @param string|null $query
     * @return MilestoneAll|MilestoneFilter
     * @throws MalformedQueryParameterException
     */
    public function convertMilestone($query)
    {
        $error_message = 'Expecting {"milestone_id":<id>}.';

        if (! isset($query)) {
            return new MilestoneAll();
        }

        $query_object = json_decode(stripslashes($query));

        if (! is_object($query_object)) {
            throw new MalformedQueryParameterException();
        }

        if (! isset($query_object->milestone_id)) {
            return new MilestoneAll();
        }

        if ($query_object->milestone_id === 0) {
            return new MilestoneAll();
        } elseif (is_int($query_object->milestone_id)) {
            return new MilestoneFilter($query_object->milestone_id);
        } else {
            throw new MalformedQueryParameterException($error_message);
        }
    }
}
