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

namespace Tuleap\CrossTracker\Report\Query\Advanced\FromBuilder;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedFrom;
use Tuleap\CrossTracker\Widget\SearchCrossTrackerWidget;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTrackerIn;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

/**
 * @template-implements FromTrackerConditionVisitor<FromTrackerBuilderVisitorParameters, IProvideParametrizedFromAndWhereSQLFragments>
 */
final readonly class FromTrackerBuilderVisitor implements FromTrackerConditionVisitor
{
    public function __construct(
        private SearchCrossTrackerWidget $widget_retriever,
    ) {
    }

    public function visitEqual(FromTrackerEqual $tracker_equal, $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from_tracker = $parameters->from_tracker;

        return match ($from_tracker->getTarget()) {
            AllowedFrom::TRACKER_NAME => $this->buildTrackerName([$tracker_equal->getValue()], $parameters),
            default                   => throw new LogicException("Unknown FROM tracker: {$from_tracker->getTarget()}"),
        };
    }

    public function visitIn(FromTrackerIn $tracker_in, $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from_tracker = $parameters->from_tracker;

        return match ($from_tracker->getTarget()) {
            AllowedFrom::TRACKER_NAME => $this->buildTrackerName($tracker_in->getValues(), $parameters),
            default                   => throw new LogicException("Unknown FROM tracker: {$from_tracker->getTarget()}"),
        };
    }

    /**
     * @param list<string> $names
     */
    private function buildTrackerName(array $names, FromTrackerBuilderVisitorParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $name_statement   = EasyStatement::open()->in('tracker.item_name IN (?*)', $names);
        $where            = (string) $name_statement;
        $where_parameters = $names;

        if ($parameters->is_tracker_condition_alone) {
            $row = $this->widget_retriever->searchCrossTrackerWidgetDashboardById($parameters->report_id);
            if ($row === null || $row['dashboard_type'] !== 'project') {
                throw new LogicException('Project id not found');
            }
            $where             .= ' AND project.group_id = ?';
            $where_parameters[] = $row['project_id'];
        }

        return new ParametrizedFromWhere('', $where, [], $where_parameters);
    }
}
