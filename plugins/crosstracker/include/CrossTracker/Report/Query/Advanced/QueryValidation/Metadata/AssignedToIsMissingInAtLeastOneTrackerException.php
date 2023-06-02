<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidQueryException;

final class AssignedToIsMissingInAtLeastOneTrackerException extends InvalidQueryException
{
    public function __construct($count)
    {
        parent::__construct(
            sprintf(
                dngettext(
                    'tuleap-crosstracker',
                    'One of the trackers involved in the query does not have the semantic contributor/assignee defined.
                    Please refine your query or check the configuration of the trackers.',
                    '%d of the trackers involved in the query do not have the semantic contributor/assignee defined.
                    Please refine your query or check the configuration of the trackers.',
                    $count
                ),
                $count
            )
        );
    }
}
