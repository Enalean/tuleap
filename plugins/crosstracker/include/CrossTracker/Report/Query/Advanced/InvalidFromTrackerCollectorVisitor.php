<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use LogicException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerIn;

/**
 * @template-implements FromTrackerConditionVisitor<InvalidFromTrackerCollectorParameters, void>
 */
final class InvalidFromTrackerCollectorVisitor implements FromTrackerConditionVisitor
{
    public function visitEqual(FromTrackerEqual $tracker_equal, $parameters)
    {
        $from_tracker = $parameters->from_tracker;

        match ($from_tracker->getTarget()) {
            AllowedFrom::TRACKER_NAME => null,
            default                   => throw new LogicException("Unknown FROM tracker: {$from_tracker->getTarget()}"),
        };
    }

    public function visitIn(FromTrackerIn $tracker_in, $parameters)
    {
        $from_tracker = $parameters->from_tracker;

        match ($from_tracker->getTarget()) {
            AllowedFrom::TRACKER_NAME => null,
            default                   => throw new LogicException("Unknown FROM tracker: {$from_tracker->getTarget()}"),
        };
    }
}
