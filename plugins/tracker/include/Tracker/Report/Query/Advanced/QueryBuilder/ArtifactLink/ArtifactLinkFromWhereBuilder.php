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
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithoutParent;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithParent;
use Tuleap\Tracker\Report\Query\FromWhere;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

final class ArtifactLinkFromWhereBuilder
{
    private const INVALID_ARTIFACT_ID = '-1';

    public function __construct(private readonly RetrieveViewableArtifact $artifact_factory)
    {
    }

    public function getFromWhereForWithParent(WithParent $term, \PFUser $user): IProvideFromAndWhereSQLFragments
    {
        $from  = '';
        $where = '(' . $this->getQueryToKnowIfMatchingArtifactHasAtLeastOneParent($term, $user) . ') = 1';

        return new FromWhere($from, $where);
    }

    public function getFromWhereForWithoutParent(WithoutParent $term, \PFUser $user): IProvideFromAndWhereSQLFragments
    {
        $from  = '';
        $where = '(' . $this->getQueryToKnowIfMatchingArtifactHasAtLeastOneParent($term, $user) . ') IS NULL';

        return new FromWhere($from, $where);
    }

    private function getQueryToKnowIfMatchingArtifactHasAtLeastOneParent(WithParent|WithoutParent $term, \PFUser $user): string
    {
        $suffix = spl_object_hash($term);

        $artifact_condition = '';
        if ($term->condition) {
            $artifact           = $this->artifact_factory->getArtifactByIdUserCanView($user, $term->condition->artifact_id);
            $artifact_condition = "AND TCA_$suffix.id = " . (int) ($artifact ? $artifact->getId() : self::INVALID_ARTIFACT_ID);
        }

        return "SELECT 1
            FROM
                tracker_changeset_value_artifactlink AS TCVAL_$suffix
                INNER JOIN tracker_changeset_value AS TCV_$suffix
                    ON (TCVAL_$suffix.changeset_value_id = TCV_$suffix.id)
                INNER JOIN tracker_artifact AS TCA_$suffix
                    ON (TCA_$suffix.last_changeset_id = TCV_$suffix.changeset_id $artifact_condition)
            WHERE TCVAL_$suffix.artifact_id = artifact.id
                AND TCVAL_$suffix.nature = '_is_child'
            LIMIT 1";
    }
}
