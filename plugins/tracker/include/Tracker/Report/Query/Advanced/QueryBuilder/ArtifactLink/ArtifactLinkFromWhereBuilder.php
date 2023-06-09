<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ArtifactLink;

use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ParentArtifactCondition;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ParentConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ParentTrackerCondition;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutParent;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithParent;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

/**
 * @template-implements ParentConditionVisitor<ArtifactLinkFromWhereBuilderParameters, ParametrizedSQLFragment>
 */
final class ArtifactLinkFromWhereBuilder implements ParentConditionVisitor
{
    private const INVALID_ARTIFACT_ID = -1;

    public function __construct(private readonly RetrieveViewableArtifact $artifact_factory)
    {
    }

    public function getFromWhereForWithParent(WithParent $term, \PFUser $user): IProvideParametrizedFromAndWhereSQLFragments
    {
        $fragment = $this->getQueryToKnowIfMatchingArtifactHasAtLeastOneParent($term, $user);

        $from  = '';
        $where = '(' . $fragment->sql . ') = 1';

        return new ParametrizedFromWhere($from, $where, [], $fragment->parameters);
    }

    public function getFromWhereForWithoutParent(WithoutParent $term, \PFUser $user): IProvideParametrizedFromAndWhereSQLFragments
    {
        $fragment = $this->getQueryToKnowIfMatchingArtifactHasAtLeastOneParent($term, $user);

        $from  = '';
        $where = '(' . $fragment->sql . ') IS NULL';

        return new ParametrizedFromWhere($from, $where, [], $fragment->parameters);
    }

    private function getQueryToKnowIfMatchingArtifactHasAtLeastOneParent(WithParent|WithoutParent $term, \PFUser $user): ParametrizedSQLFragment
    {
        $suffix = spl_object_hash($term);

        if ($term->condition) {
            return $term->condition->accept($this, new ArtifactLinkFromWhereBuilderParameters($user, $suffix));
        }

        return new ParametrizedSQLFragment(
            "SELECT 1
            FROM
                tracker_changeset_value_artifactlink AS TCVAL_$suffix
                INNER JOIN tracker_changeset_value AS TCV_$suffix
                    ON (TCVAL_$suffix.changeset_value_id = TCV_$suffix.id)
                INNER JOIN tracker_artifact AS TCA_$suffix
                    ON (TCA_$suffix.last_changeset_id = TCV_$suffix.changeset_id)
            WHERE TCVAL_$suffix.artifact_id = artifact.id
                AND TCVAL_$suffix.nature = '_is_child'
            LIMIT 1",
            [],
        );
    }

    public function visitParentArtifactCondition(ParentArtifactCondition $condition, $parameters)
    {
        $suffix = $parameters->suffix;

        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($parameters->user, $condition->artifact_id);

        return new ParametrizedSQLFragment(
            "SELECT 1
            FROM
                tracker_changeset_value_artifactlink AS TCVAL_$suffix
                INNER JOIN tracker_changeset_value AS TCV_$suffix
                    ON (TCVAL_$suffix.changeset_value_id = TCV_$suffix.id)
                INNER JOIN tracker_artifact AS TCA_$suffix
                    ON (
                        TCA_$suffix.last_changeset_id = TCV_$suffix.changeset_id AND
                        TCA_$suffix.id = ?
                    )
            WHERE TCVAL_$suffix.artifact_id = artifact.id
                AND TCVAL_$suffix.nature = '_is_child'
            LIMIT 1",
            [
                ($artifact ? $artifact->getId() : self::INVALID_ARTIFACT_ID),
            ]
        );
    }

    public function visitParentTrackerCondition(ParentTrackerCondition $condition, $parameters)
    {
        $suffix = $parameters->suffix;

        return new ParametrizedSQLFragment(
            "SELECT 1
            FROM
                tracker_changeset_value_artifactlink AS TCVAL_$suffix
                INNER JOIN tracker_changeset_value AS TCV_$suffix
                    ON (TCVAL_$suffix.changeset_value_id = TCV_$suffix.id)
                INNER JOIN tracker_artifact AS TCA_$suffix
                    ON (TCA_$suffix.last_changeset_id = TCV_$suffix.changeset_id)
                INNER JOIN tracker AS T_$suffix
                    ON (T_$suffix.id = TCA_$suffix.tracker_id AND
                        T_$suffix.item_name = ?
                    )
            WHERE TCVAL_$suffix.artifact_id = artifact.id
                AND TCVAL_$suffix.nature = '_is_child'
            LIMIT 1",
            [
                $condition->tracker_name,
            ],
        );
    }
}
