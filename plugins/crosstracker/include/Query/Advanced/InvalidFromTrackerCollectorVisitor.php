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

namespace Tuleap\CrossTracker\Query\Advanced;

use LogicException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerIn;

/**
 * @template-implements FromTrackerConditionVisitor<InvalidFromTrackerCollectorParameters, void>
 */
final readonly class InvalidFromTrackerCollectorVisitor implements FromTrackerConditionVisitor
{
    public function __construct(
        private WidgetInProjectChecker $in_project_checker,
    ) {
    }

    public function visitEqual(FromTrackerEqual $tracker_equal, $parameters): void
    {
        $from_tracker = $parameters->from_tracker;

        match ($from_tracker->getTarget()) {
            AllowedFrom::TRACKER_NAME => $this->checkTrackerName([$tracker_equal->getValue()], $parameters),
            default                   => throw new LogicException("Unknown FROM tracker: {$from_tracker->getTarget()}"),
        };
    }

    public function visitIn(FromTrackerIn $tracker_in, $parameters): void
    {
        $from_tracker = $parameters->from_tracker;

        match ($from_tracker->getTarget()) {
            AllowedFrom::TRACKER_NAME => $this->checkTrackerName($tracker_in->getValues(), $parameters),
            default                   => throw new LogicException("Unknown FROM tracker: {$from_tracker->getTarget()}"),
        };
    }

    /**
     * @param list<string> $names
     */
    private function checkTrackerName(array $names, InvalidFromTrackerCollectorParameters $parameters): void
    {
        foreach ($names as $name) {
            if ($name === '') {
                $parameters->collection->addInvalidFrom(dgettext('tuleap-crosstracker', '@tracker.name cannot be empty'));
                return;
            }
        }

        if ($parameters->is_tracker_condition_alone) {
            $parameters->widget_id->match(
                function (int $widget_id) use ($parameters): void {
                    if (! $this->in_project_checker->isWidgetInProjectDashboard($widget_id)) {
                        $parameters->collection->addInvalidFrom(dgettext(
                            'tuleap-crosstracker',
                            'In the context of a personal dashboard, you must provide a @project condition in the FROM part of your query',
                        ));
                    }
                },
                function () use ($parameters): void {
                    $parameters->collection->addInvalidFrom(dgettext(
                        'tuleap-crosstracker',
                        'You must provide a @project condition in the FROM part of your query when you are not in a personal dashboard',
                    ));
                }
            );
        }
    }
}
