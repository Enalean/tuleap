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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\From;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProject;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromSomethingVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromTracker;
use Tuleap\Tracker\Report\Query\Advanced\IBuildInvalidFromCollection;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFromCollection;

/**
 * @template-implements FromSomethingVisitor<InvalidFromCollectionParameters, void>
 */
final readonly class InvalidFromCollectionBuilder implements IBuildInvalidFromCollection, FromSomethingVisitor
{
    public function __construct(
        private InvalidFromTrackerCollectorVisitor $from_tracker_condition_visitor,
        private InvalidFromProjectCollectorVisitor $from_project_condition_visitor,
        private int $widget_id,
    ) {
    }

    public function buildCollectionOfInvalidFrom(From $from, PFUser $user): InvalidFromCollection
    {
        $collection = new InvalidFromCollection();
        if ($from->getRight() !== null && $from->getLeft()::class === $from->getRight()::class) {
            $collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                'The both conditions of \'FROM\' must be on "tracker" and "project". If you want to search on several trackers or projects, please use \'IN\' operator instead. (e.g @tracker.name IN(\'t1\', ...))',
            ));
            return $collection;
        }

        $from->getLeft()->acceptFromSomethingVisitor($this, new InvalidFromCollectionParameters($collection, $this->widget_id, $from->getRight() === null, $user));
        $from->getRight()?->acceptFromSomethingVisitor($this, new InvalidFromCollectionParameters($collection, $this->widget_id, false, $user));

        return $collection;
    }

    public function visitTracker(FromTracker $from_tracker, $parameters): void
    {
        if (! in_array($from_tracker->getTarget(), AllowedFrom::ALLOWED_TRACKER)) {
            $parameters->collection->addInvalidFrom(sprintf(
                dgettext('tuleap-crosstracker', "You cannot search on '%s', please refer to the documentation about the 'FROM' syntax part."),
                $from_tracker->getTarget(),
            ));
            return;
        }

        $from_tracker->getCondition()->acceptFromTrackerConditionVisitor(
            $this->from_tracker_condition_visitor,
            new InvalidFromTrackerCollectorParameters($from_tracker, $parameters->collection, $parameters->is_condition_alone, $parameters->widget_id),
        );
    }

    public function visitProject(FromProject $from_project, $parameters): void
    {
        if (! in_array($from_project->getTarget(), AllowedFrom::ALLOWED_PROJECT)) {
            $parameters->collection->addInvalidFrom(sprintf(
                dgettext('tuleap-crosstracker', "You cannot search on '%s', please refer to the documentation about the 'FROM' syntax part."),
                $from_project->getTarget(),
            ));
            return;
        }

        $from_project->getCondition()->acceptFromProjectConditionVisitor(
            $this->from_project_condition_visitor,
            new InvalidFromProjectCollectorParameters($from_project, $parameters->collection, $parameters->widget_id, $parameters->user),
        );
    }
}
