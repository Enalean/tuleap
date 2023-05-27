<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use CodendiDataAccess;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

class MetadataNotEqualComparisonFromWhereBuilder implements MetadataComparisonFromWhereBuilder, ValueWrapperVisitor
{
    public function getFromWhere(Metadata $metadata, Comparison $comparison): IProvideFromAndWhereSQLFragments
    {
        $value = $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));

        if ($value === '') {
            return $this->searchArtifactsWithComments($comparison);
        }

        return $this->searchComment($comparison, $value);
    }

    public function visitCurrentDateTimeValueWrapper(
        CurrentDateTimeValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitInValueWrapper(
        InValueWrapper $collection_of_value_wrappers,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitCurrentUserValueWrapper(
        CurrentUserValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitStatusOpenValueWrapper(
        StatusOpenValueWrapper $value_wrapper,
        ValueWrapperParameters $parameters,
    ) {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    private function quoteSmart($value)
    {
        return $this->removeEnclosingSimpleQuoteToNotFailMatchSqlQuery(
            CodendiDataAccess::instance()->quoteSmart($value)
        );
    }

    private function removeEnclosingSimpleQuoteToNotFailMatchSqlQuery($value)
    {
        return trim($value, "'");
    }

    /**
     * @return string
     */
    protected function surroundValueWithSimpleAndThenDoubleQuotesForFulltextMatching($value)
    {
        return '\'"' . $value . '"\'';
    }

    protected function searchComment(Comparison $comparison, $value): IProvideFromAndWhereSQLFragments
    {
        $value = $this->quoteSmart($value);
        $value = $this->surroundValueWithSimpleAndThenDoubleQuotesForFulltextMatching($value);

        $suffix = spl_object_hash($comparison);

        $from = " LEFT JOIN (
                    tracker_changeset_comment_fulltext AS TCCF_$suffix
                    INNER JOIN tracker_changeset_comment AS TCC_$suffix
                     ON (
                        TCC_$suffix.id = TCCF_$suffix.comment_id
                        AND TCC_$suffix.parent_id = 0
                        AND match(TCCF_$suffix.stripped_body) against ($value IN BOOLEAN MODE)
                     )
                     INNER JOIN tracker_changeset AS TC_$suffix ON TC_$suffix.id = TCC_$suffix.changeset_id
                 ) ON TC_$suffix.artifact_id = artifact.id";

        $where = "TCC_$suffix.changeset_id IS NULL";

        return new FromWhere($from, $where);
    }

    private function searchArtifactsWithComments(Comparison $comparison): IProvideFromAndWhereSQLFragments
    {
        $suffix = spl_object_hash($comparison);

        $from = " LEFT JOIN (
                    tracker_changeset AS TC_$suffix
                    JOIN tracker_changeset_comment AS TCC_$suffix
                       ON TC_$suffix.id = TCC_$suffix.changeset_id
                    JOIN tracker_changeset_comment_fulltext AS TCCF_$suffix
                        ON TCC_$suffix.id = TCCF_$suffix.comment_id
                    ) ON TC_$suffix.artifact_id = artifact.id";

        $where = "TCCF_$suffix.comment_id IS NOT NULL";

        return new FromWhere($from, $where);
    }
}
