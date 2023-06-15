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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\ArtifactLink;

use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkArtifactCondition;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkConditionVisitor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkTrackerCondition;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutChildren;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithChildren;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ArtifactLink\ArtifactLinkFromWhereBuilderParameters;

/**
 * @template-implements LinkConditionVisitor<ArtifactLinkFromWhereBuilderParameters, array{0: string, 1: array}>
 */
final class LinkFromWhereBuilder implements LinkConditionVisitor
{
    private const INVALID_ARTIFACT_ID = -1;

    public function __construct(private readonly RetrieveViewableArtifact $artifact_factory)
    {
    }

    public function getFromWhereForWithChildren(WithChildren $term, \PFUser $user): IProvideParametrizedFromAndWhereSQLFragments
    {
        [$sql, $parameters] = $this->getQueryToKnowIfMatchingArtifactHasAtLeastOneChildren($term, $user);

        $from  = '';
        $where = '(' . $sql . ') = 1';

        return new ParametrizedFromWhere($from, $where, [], $parameters);
    }

    public function getFromWhereForWithoutChildren(WithoutChildren $term, \PFUser $user): IProvideParametrizedFromAndWhereSQLFragments
    {
        [$sql, $parameters] = $this->getQueryToKnowIfMatchingArtifactHasAtLeastOneChildren($term, $user);

        $from  = '';
        $where = '(' . $sql . ') IS NULL';

        return new ParametrizedFromWhere($from, $where, [], $parameters);
    }

    /**
     *
     * @return array{0: string, 1: array}
     */
    private function getQueryToKnowIfMatchingArtifactHasAtLeastOneChildren(WithChildren|WithoutChildren $term, \PFUser $user): array
    {
        $suffix = spl_object_hash($term);

        if ($term->condition) {
            return $term->condition->accept($this, new ArtifactLinkFromWhereBuilderParameters($user, $suffix));
        }

        return [
            "SELECT 1
                FROM
                    tracker_changeset_value_artifactlink AS TCVAL_$suffix
                    INNER JOIN tracker_changeset_value AS TCV_$suffix
                        ON (TCVAL_$suffix.changeset_value_id = TCV_$suffix.id
                            AND tracker_artifact.last_changeset_id = TCV_$suffix.changeset_id
                            AND TCVAL_$suffix.nature = '_is_child'
                        )
                LIMIT 1",
            [],
        ];
    }

    public function visitLinkArtifactCondition(LinkArtifactCondition $condition, $parameters)
    {
        $suffix = $parameters->suffix;

        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($parameters->user, $condition->artifact_id);

        return [
            "SELECT 1
            FROM
                tracker_changeset_value_artifactlink AS TCVAL_$suffix
                INNER JOIN tracker_changeset_value AS TCV_$suffix
                    ON (TCVAL_$suffix.changeset_value_id = TCV_$suffix.id
                        AND tracker_artifact.last_changeset_id = TCV_$suffix.changeset_id
                        AND TCVAL_$suffix.artifact_id = ?
                        AND TCVAL_$suffix.nature = '_is_child'
                    )
            LIMIT 1",
            [
                ($artifact ? $artifact->getId() : self::INVALID_ARTIFACT_ID),
            ],
        ];
    }

    public function visitLinkTrackerCondition(LinkTrackerCondition $condition, $parameters)
    {
        $suffix = $parameters->suffix;

        return [
            "SELECT 1
            FROM
                tracker_changeset_value_artifactlink AS TCVAL_$suffix
                INNER JOIN tracker_changeset_value AS TCV_$suffix
                    ON (TCVAL_$suffix.changeset_value_id = TCV_$suffix.id
                        AND tracker_artifact.last_changeset_id = TCV_$suffix.changeset_id
                        AND TCVAL_$suffix.nature = '_is_child'
                    )
                INNER JOIN tracker_artifact AS TCA_$suffix
                    ON (TCA_$suffix.id = TCVAL_$suffix.artifact_id)
                INNER JOIN tracker AS T_$suffix
                    ON (T_$suffix.id = TCA_$suffix.tracker_id
                        AND T_$suffix.item_name = ?
                    )
            LIMIT 1",
            [
                $condition->tracker_name,
            ],
        ];
    }
}
