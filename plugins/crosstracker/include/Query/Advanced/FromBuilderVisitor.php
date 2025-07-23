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

use PFUser;
use Tuleap\CrossTracker\Query\Advanced\FromBuilder\FromProjectBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\FromBuilder\FromProjectBuilderVisitorParameters;
use Tuleap\CrossTracker\Query\Advanced\FromBuilder\FromTrackerBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\FromBuilder\FromTrackerBuilderVisitorParameters;
use Tuleap\Option\Option;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\From;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProject;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromSomethingVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTracker;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedAndFromWhere;

/**
 * @template-implements FromSomethingVisitor<FromBuilderVisitorParameters, IProvideParametrizedFromAndWhereSQLFragments>
 */
final readonly class FromBuilderVisitor implements FromSomethingVisitor
{
    public function __construct(
        private FromTrackerBuilderVisitor $tracker_builder,
        private FromProjectBuilderVisitor $project_builder,
    ) {
    }

    /**
     * @param Option<int> $widget_id
     */
    public function buildFromWhere(From $from, Option $widget_id, PFUser $user): IProvideParametrizedFromAndWhereSQLFragments
    {
        $left = $from->getLeft()->acceptFromSomethingVisitor($this, new FromBuilderVisitorParameters($widget_id, $from->getRight() === null, $user));
        if ($from->getRight() === null) {
            return $left;
        }
        $right = $from->getRight()->acceptFromSomethingVisitor($this, new FromBuilderVisitorParameters($widget_id, false, $user));

        return new ParametrizedAndFromWhere($left, $right);
    }

    public function visitTracker(FromTracker $from_tracker, $parameters)
    {
        return $from_tracker->getCondition()->acceptFromTrackerConditionVisitor(
            $this->tracker_builder,
            new FromTrackerBuilderVisitorParameters($from_tracker, $parameters->widget_id, $parameters->is_condition_alone),
        );
    }

    public function visitProject(FromProject $from_project, $parameters)
    {
        return $from_project->getCondition()->acceptFromProjectConditionVisitor(
            $this->project_builder,
            new FromProjectBuilderVisitorParameters($from_project, $parameters->widget_id, $parameters->user),
        );
    }
}
