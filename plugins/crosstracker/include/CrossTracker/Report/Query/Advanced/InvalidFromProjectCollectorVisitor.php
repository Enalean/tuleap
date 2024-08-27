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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectIn;

/**
 * @template-implements FromProjectConditionVisitor<InvalidFromProjectCollectorParameters, void>
 */
final readonly class InvalidFromProjectCollectorVisitor implements FromProjectConditionVisitor
{
    public function __construct(
        private WidgetInProjectChecker $in_project_checker,
    ) {
    }

    public function visitEqual(FromProjectEqual $project_equal, $parameters): void
    {
        $from_project = $parameters->from_project;

        match ($from_project->getTarget()) {
            AllowedFrom::PROJECT          => $this->checkProjectEqual($project_equal, $parameters),
            AllowedFrom::PROJECT_NAME,
            AllowedFrom::PROJECT_CATEGORY => null,
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
    }

    private function checkProjectEqual(FromProjectEqual $project_equal, InvalidFromProjectCollectorParameters $parameters): void
    {
        if ($project_equal->getValue() !== AllowedFrom::PROJECT_SELF) {
            $parameters->collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                "Only @project = 'self' is supported",
            ));
            return;
        }

        if (! $this->in_project_checker->isWidgetInProjectDashboard($parameters->report_id)) {
            $parameters->collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                "You cannot use @project = 'self' in the context of a personal dashboard",
            ));
        }
    }

    public function visitIn(FromProjectIn $project_in, $parameters): void
    {
        $from_project = $parameters->from_project;

        match ($from_project->getTarget()) {
            AllowedFrom::PROJECT          => $parameters->collection->addInvalidFrom(dgettext('tuleap-crosstracker', "You cannot use '@project IN(...)'")),
            AllowedFrom::PROJECT_NAME,
            AllowedFrom::PROJECT_CATEGORY => null,
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
    }
}
