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

namespace Tuleap\CrossTracker\Query\Advanced\FromBuilder;

use LogicException;
use ParagonIE\EasyDB\EasyStatement;
use PFUser;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CrossTracker\Query\Advanced\AllowedFrom;
use Tuleap\CrossTracker\Query\Advanced\InvalidFromProjectCollectorVisitor;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\RetrieveCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\Sidebar\CollectLinkedProjects;
use Tuleap\Project\Sidebar\LinkedProject;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectEqual;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\FromProjectIn;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

/**
 * @template-implements FromProjectConditionVisitor<FromProjectBuilderVisitorParameters, IProvideParametrizedFromAndWhereSQLFragments>
 */
final readonly class FromProjectBuilderVisitor implements FromProjectConditionVisitor
{
    public function __construct(
        private ProjectByIDFactory $project_factory,
        private EventDispatcherInterface $event_dispatcher,
        private RetrieveCrossTrackerWidget $cross_tracker_widget_retriever,
    ) {
    }

    #[\Override]
    public function visitEqual(FromProjectEqual $project_equal, $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        if (is_array($project_equal->getValue())) {
            $projects_ids = array_map(static fn(Project $project) => $project->getId(), $project_equal->getValue());
            if ($projects_ids === []) {
                throw new LogicException(
                    sprintf(
                        'Should be already checked in %s',
                        InvalidFromProjectCollectorVisitor::class
                    )
                );
            }
            return new ParametrizedFromWhere(
                '',
                EasyStatement::open()->in('project.group_id IN (?*)', $projects_ids),
                [],
                $projects_ids,
            );
        }
        $from_project = $parameters->from_project;

        return match ($from_project->getTarget()) {
            AllowedFrom::PROJECT          => $this->buildFromProjectEqual($project_equal, $parameters),
            AllowedFrom::PROJECT_NAME     => $this->buildFromProjectName([$project_equal->getValue()]),
            AllowedFrom::PROJECT_CATEGORY => $this->buildFromProjectCategoryEqual($project_equal->getValue()),
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
    }

    private function buildFromProjectEqual(FromProjectEqual $project_equal, FromProjectBuilderVisitorParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        if ($project_equal->getValue() === AllowedFrom::PROJECT_SELF) {
            $project_id = $this->getCurrentProjectId($parameters);
            return new ParametrizedFromWhere('', 'project.group_id = ?', [], [$project_id]);
        }

        if ($project_equal->getValue() === AllowedFrom::PROJECT_AGGREGATED) {
            $projects_ids = $this->getAggregatedProjectsIds($parameters);

            return new ParametrizedFromWhere(
                '',
                EasyStatement::open()->in('project.group_id IN (?*)', $projects_ids),
                [],
                $projects_ids,
            );
        }

        throw new LogicException('Should not be here: already catched by the FROM query validation');
    }

    private function getCurrentProjectId(FromProjectBuilderVisitorParameters $parameters): int
    {
        return $parameters->widget_id->match(
            function (int $widget_id): int {
                return $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id)
                    ->match(
                        function (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget): int {
                            if ($widget instanceof UserCrossTrackerWidget) {
                                throw new LogicException('Project id not found');
                            }
                            assert($widget instanceof ProjectCrossTrackerWidget);
                            return $widget->getProjectId();
                        },
                        function (): never {
                            throw new LogicException('Project or user id not found');
                        }
                    );
            },
            function (): never {
                throw new LogicException('Could not find the project ID of a query not associated with a widget');
            }
        );
    }

    /**
     * @return list<int>
     */
    private function getAggregatedProjectsIds(FromProjectBuilderVisitorParameters $parameters): array
    {
        return $parameters->widget_id->match(
            /** @return list<int> */
            function (int $widget_id) use ($parameters): array {
                return $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id)
                    ->match(
                        fn (ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) => $this->getProjectIdsFromValidWidget($widget, $parameters->user),
                        fn (): never => throw new LogicException('Project id not found')
                    );
            },
            function (): never {
                throw new LogicException('Could not find the aggregated projects of a query not associated with a widget');
            }
        );
    }

    /**
     * @return list<int>
     */
    private function getProjectIdsFromValidWidget(
        ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget,
        PFUser $user,
    ): array {
        if ($widget instanceof UserCrossTrackerWidget) {
            throw new LogicException('Project id not found');
        }

        $project = $this->project_factory->getValidProjectById($widget->getProjectId());

        $linked_projects = $this->event_dispatcher->dispatch(new CollectLinkedProjects($project, $user));
        assert($linked_projects instanceof CollectLinkedProjects);

        return array_values(array_map(
            static fn(LinkedProject $project) => $project->id,
            $linked_projects->getChildrenProjects()->getProjects(),
        ));
    }

    private function buildFromProjectCategoryEqual(string $category): IProvideParametrizedFromAndWhereSQLFragments
    {
        $formatted_category = self::formatCategory($category) . '%';

        $from = <<<SQL
        INNER JOIN trove_group_link AS tgl ON (tgl.group_id = project.group_id)
        INNER JOIN trove_cat AS tc ON (tc.trove_cat_id = tgl.trove_cat_id AND tc.fullpath LIKE ?)
        SQL;

        return new ParametrizedFromWhere($from, '', [$formatted_category], []);
    }

    /**
     * @param list<string> $names
     */
    private function buildFromProjectName(array $names): IProvideParametrizedFromAndWhereSQLFragments
    {
        return new ParametrizedFromWhere(
            '',
            EasyStatement::open()->in('project.unix_group_name IN (?*)', $names),
            [],
            $names,
        );
    }

    #[\Override]
    public function visitIn(FromProjectIn $project_in, $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from_project = $parameters->from_project;

        return match ($from_project->getTarget()) {
            AllowedFrom::PROJECT          => $this->buildFromProjectIn($project_in->getValues(), $parameters),
            AllowedFrom::PROJECT_NAME     => $this->buildFromProjectName($project_in->getValues()),
            AllowedFrom::PROJECT_CATEGORY => $this->buildFromProjectCategoryIn($project_in->getValues()),
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
    }

    /**
     * @param list<string> $values
     */
    private function buildFromProjectIn(array $values, FromProjectBuilderVisitorParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $projects_ids = array_reduce(
            $values,
            fn(array $accumulator, string $value) => match ($value) {
                AllowedFrom::PROJECT_SELF       => [...$accumulator, $this->getCurrentProjectId($parameters)],
                AllowedFrom::PROJECT_AGGREGATED => [...$accumulator, ...$this->getAggregatedProjectsIds($parameters)],
            },
            [],
        );

        return new ParametrizedFromWhere(
            '',
            EasyStatement::open()->in('project.group_id IN (?*)', $projects_ids),
            [],
            $projects_ids,
        );
    }

    /**
     * @param list<string> $categories
     */
    private function buildFromProjectCategoryIn(array $categories): IProvideParametrizedFromAndWhereSQLFragments
    {
        $trove_cat_statement = EasyStatement::open()->in(
            'tc.fullpath IN(?*)',
            array_map(self::formatCategory(...), $categories),
        );

        $from = <<<SQL
        INNER JOIN trove_group_link AS tgl ON (tgl.group_id = project.group_id)
        INNER JOIN trove_cat AS tc ON (tc.trove_cat_id = tgl.trove_cat_id AND $trove_cat_statement)
        SQL;

        return new ParametrizedFromWhere($from, '', array_values($trove_cat_statement->values()), []);
    }

    private static function formatCategory(string $category): string
    {
        return implode(
            ' :: ',
            array_map(
                static fn(string $part): string => trim($part),
                explode('::', $category),
            )
        );
    }
}
