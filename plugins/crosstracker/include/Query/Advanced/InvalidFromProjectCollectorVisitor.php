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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CrossTracker\Widget\SearchCrossTrackerWidget;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
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
        private SearchCrossTrackerWidget $widget_retriever,
        private ProjectByIDFactory $project_factory,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function visitEqual(FromProjectEqual $project_equal, $parameters): void
    {
        if (is_array($project_equal->getValue())) {
            if ($project_equal->getValue() === []) {
                $parameters->collection->addInvalidFrom(
                    dgettext(
                        'tuleap-crosstracker',
                        'No tracker found',
                    )
                );
            }
            return;
        }

        $from_project = $parameters->from_project;

        match ($from_project->getTarget()) {
            AllowedFrom::PROJECT          => $this->checkProjectEqual($project_equal, $parameters),
            AllowedFrom::PROJECT_NAME     => $this->checkProjectNames([$project_equal->getValue()], $parameters),
            AllowedFrom::PROJECT_CATEGORY => $this->checkProjectCategories([$project_equal->getValue()], $parameters),
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
    }

    private function checkProjectEqual(FromProjectEqual $project_equal, InvalidFromProjectCollectorParameters $parameters): void
    {
        match ($project_equal->getValue()) {
            AllowedFrom::PROJECT_SELF       => $this->checkProjectSelf($parameters),
            AllowedFrom::PROJECT_AGGREGATED => $this->checkProjectAggregated($parameters),
            default                         => $parameters->collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                "Only @project = 'self' and @project = 'aggregated' are supported",
            )),
        };
    }

    /**
     * @param list<string> $categories
     */
    private function checkProjectCategories(array $categories, InvalidFromProjectCollectorParameters $parameters): void
    {
        foreach ($categories as $category) {
            if ($category === '') {
                $parameters->collection->addInvalidFrom(dgettext('tuleap-crosstracker', '@project.category cannot be empty'));
                return;
            }
        }
    }

    /**
     * @param list<string> $names
     */
    private function checkProjectNames(array $names, InvalidFromProjectCollectorParameters $parameters): void
    {
        foreach ($names as $name) {
            if ($name === '') {
                $parameters->collection->addInvalidFrom(dgettext('tuleap-crosstracker', '@project.name cannot be empty'));
            }
        }
    }

    public function visitIn(FromProjectIn $project_in, $parameters): void
    {
        $from_project = $parameters->from_project;

        match ($from_project->getTarget()) {
            AllowedFrom::PROJECT          => $this->checkProjectIn($project_in->getValues(), $parameters),
            AllowedFrom::PROJECT_NAME     => $this->checkProjectNames($project_in->getValues(), $parameters),
            AllowedFrom::PROJECT_CATEGORY => $this->checkProjectCategories($project_in->getValues(), $parameters),
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
    }

    /**
     * @param list<string> $values
     */
    private function checkProjectIn(array $values, InvalidFromProjectCollectorParameters $parameters): void
    {
        if (count(array_unique($values)) !== count($values)) {
            $parameters->collection->addInvalidFrom(dgettext('tuleap-crosstracker', '@project IN(...) cannot contain multiple times the same value'));
            return;
        }

        foreach ($values as $value) {
            match ($value) {
                AllowedFrom::PROJECT_SELF       => $this->checkProjectSelf($parameters),
                AllowedFrom::PROJECT_AGGREGATED => $this->checkProjectAggregated($parameters),
                default                         => $parameters->collection->addInvalidFrom(
                    dgettext('tuleap-crosstracker', "Only 'self' and 'aggregated' are supported for @project IN(...)"),
                ),
            };
        }
    }

    private function checkProjectSelf(InvalidFromProjectCollectorParameters $parameters): void
    {
        if (! $this->in_project_checker->isWidgetInProjectDashboard($parameters->widget_id)) {
            $parameters->collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                "You cannot use @project with 'self' in the context of a personal dashboard",
            ));
        }
    }

    private function checkProjectAggregated(InvalidFromProjectCollectorParameters $parameters): void
    {
        $row = $this->widget_retriever->searchCrossTrackerWidgetDashboardById($parameters->widget_id);
        if ($row === null || $row['dashboard_type'] !== 'project') {
            $parameters->collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                "You cannot use @project with 'aggregated' in the context of a personal dashboard",
            ));
            return;
        }
        $project         = $this->project_factory->getValidProjectById($row['project_id']);
        $linked_projects = $this->event_dispatcher->dispatch(new CollectLinkedProjects($project, $parameters->user));
        assert($linked_projects instanceof CollectLinkedProjects);
        if (! $linked_projects->canAggregateProjects()) {
            $parameters->collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                "You cannot use @project with 'aggregated' in a project without service Program enabled",
            ));
        }
        if (! $linked_projects->getParentProjects()->isEmpty()) {
            $parameters->collection->addInvalidFrom(dgettext(
                'tuleap-crosstracker',
                "You can use @project with 'aggregated' only in a Program project",
            ));
            return;
        }
    }
}
