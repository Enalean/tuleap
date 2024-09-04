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
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerSearch;
use Tuleap\Dashboard\Project\IRetrieveProjectFromWidget;
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
        private IRetrieveProjectFromWidget $project_id_retriever,
    ) {
    }

    public function visitEqual(FromProjectEqual $project_equal, $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from_project = $parameters->from_project;

        return match ($from_project->getTarget()) {
            AllowedFrom::PROJECT          => $this->buildFromProjectEqual($project_equal, $parameters),
            AllowedFrom::PROJECT_NAME     => throw new LogicException('Should not be called: @project.name is not yet implemented'),
            AllowedFrom::PROJECT_CATEGORY => $this->buildFromProjectCategoryEqual($project_equal->getValue()),
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
    }

    private function buildFromProjectEqual(FromProjectEqual $project_equal, FromProjectBuilderVisitorParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        if ($project_equal->getValue() === AllowedFrom::PROJECT_SELF) {
            $project_id = $this->project_id_retriever->searchProjectIdFromWidgetIdAndType($parameters->report_id, ProjectCrossTrackerSearch::NAME);
            if ($project_id === null) {
                throw new LogicException('Project id not found');
            }
            return new ParametrizedFromWhere('', 'project.group_id = ?', [], [$project_id]);
        }

        throw new LogicException('Should not be here: already catched by the FROM query validation');
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

    public function visitIn(FromProjectIn $project_in, $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        $from_project = $parameters->from_project;

        return match ($from_project->getTarget()) {
            AllowedFrom::PROJECT_NAME     => throw new LogicException('Should not be called: @project.name is not yet implemented'),
            AllowedFrom::PROJECT          => throw new LogicException('Should not be called: already catched by the FROM query validation'),
            AllowedFrom::PROJECT_CATEGORY => $this->buildFromProjectCategoryIn($project_in->getValues()),
            default                       => throw new LogicException("Unknown FROM project: {$from_project->getTarget()}"),
        };
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
